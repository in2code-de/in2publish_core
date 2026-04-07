import mysql from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';
import { execSync } from 'child_process';

/**
 * Direct database and fileadmin restore for Playwright tests.
 *
 * Database: Connects to MySQL and re-imports dump data.
 * Fileadmin: Restores fileadmin from backup to both instances.
 *
 * Supports two execution contexts (auto-detected):
 * - Docker: Running inside the Playwright container (host=mysql, LOAD DATA INFILE)
 * - Host: Running on the developer's machine (host=127.0.0.1, LOAD DATA LOCAL INFILE)
 *
 * And two project contexts (auto-detected):
 * - Monorepo: Playwright at /work/packages/in2publish_core, app dirs at /work/app/{local,foreign}/
 * - Standalone: Playwright at /work, app dirs at /work/Build/{local,foreign}/
 *
 * Environment Variables:
 * - DB_HOST: MySQL host (default: auto-detect 'mysql' in Docker, '127.0.0.1' on host)
 * - DB_PORT: MySQL port (default: 3306 in Docker, 54444 on host)
 * - SQLDUMPSDIR: MySQL LOAD DATA path prefix (Docker only)
 */
// in2publish_core package root (3 levels up from Tests/Playwright/helpers/)
const PACKAGE_ROOT = path.resolve(__dirname, '../../..');

// Detect monorepo vs standalone context:
// In monorepo, PACKAGE_ROOT is /work/packages/in2publish_core and /work/app/ exists.
// In standalone, PACKAGE_ROOT is /work and Build/local/ exists instead.
const isMonorepo = fs.existsSync(path.resolve(PACKAGE_ROOT, '../../app'));
const APP_ROOT = isMonorepo ? path.resolve(PACKAGE_ROOT, '../..') : PACKAGE_ROOT;
const APP_PREFIX = isMonorepo ? 'app' : 'Build';

// Detect Docker vs host: inside Docker, /.dockerenv exists.
const isDocker = fs.existsSync('/.dockerenv');

const DUMPS_DIR_PW = path.join(PACKAGE_ROOT, '.project/data/dumps');
// MySQL LOAD DATA INFILE path: volume mount is ${SQLDUMPSDIR}:/${SQLDUMPSDIR},
// so the container path is /${SQLDUMPSDIR} (with leading ./ stripped).
const SQLDUMPSDIR = process.env.SQLDUMPSDIR || '.project/data/dumps';
const DUMPS_DIR_MYSQL = '/' + SQLDUMPSDIR.replace(/^\.\//, '').replace(/\/$/, '');
const FILEADMIN_BACKUP = path.join(PACKAGE_ROOT, '.project/data/fileadmin');
const LOCAL_FILEADMIN = path.join(APP_ROOT, APP_PREFIX, 'local/public/fileadmin');
const FOREIGN_FILEADMIN = path.join(APP_ROOT, APP_PREFIX, 'foreign/public/fileadmin');
const LOCAL_VAR_CACHE = path.join(APP_ROOT, APP_PREFIX, 'local/var/cache');
const FOREIGN_VAR_CACHE = path.join(APP_ROOT, APP_PREFIX, 'foreign/var/cache');

const DB_HOST = process.env.DB_HOST || (isDocker ? 'mysql' : '127.0.0.1');
const DB_PORT = parseInt(process.env.DB_PORT || (isDocker ? '3306' : '54444'), 10);

/**
 * Restore both local and foreign databases from dump files.
 */
export async function restoreDatabases(): Promise<void> {
    const connection = await mysql.createConnection({
        host: DB_HOST,
        port: DB_PORT,
        user: 'root',
        password: 'root',
        multipleStatements: true,
        // LOAD DATA LOCAL INFILE requires this flag on the client side
        ...(!isDocker ? { flags: ['+LOCAL_FILES'] } : {}),
    });

    console.log(`[direct-restore] Connecting to MySQL at ${DB_HOST}:${DB_PORT} (${isDocker ? 'Docker' : 'host'} mode)`);

    try {
        for (const db of ['local', 'foreign']) {
            console.log(`[direct-restore] Restoring ${db} database...`);
            await connection.query(`USE \`${db}\``);

            // Execute preamble (DROP TABLE IF EXISTS + CREATE TABLE statements)
            const preamblePath = path.join(DUMPS_DIR_PW, db, '_preamble.sql');
            const preamble = fs.readFileSync(preamblePath, 'utf8').trim();
            if (preamble) {
                await connection.query("SET sql_mode = ''");
                await connection.query(preamble);
            }

            // Fix columns that preamble creates as NOT NULL without DEFAULT,
            // which causes INSERT failures when creating new pages via the wizard.
            await connection.query(
                "ALTER TABLE `pages` MODIFY COLUMN `link` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL"
            ).catch(() => {});
            await connection.query(
                "ALTER TABLE `pages` MODIFY COLUMN `canonical_link` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL"
            ).catch(() => {});

            // Get list of tables
            const [rows] = await connection.query('SHOW TABLES') as any;
            const tables = rows.map((row: any) => Object.values(row)[0] as string);

            // For each table with a CSV, truncate and import
            for (const table of tables) {
                const csvPathPw = path.join(DUMPS_DIR_PW, db, `${table}.csv`);
                if (fs.existsSync(csvPathPw)) {
                    await connection.query(`TRUNCATE TABLE \`${table}\``);
                    if (isDocker) {
                        // Inside Docker: MySQL server can read the file directly
                        const csvPathMysql = `${DUMPS_DIR_MYSQL}/${db}/${table}.csv`;
                        await connection.query(
                            `LOAD DATA INFILE '${csvPathMysql}' INTO TABLE \`${table}\``
                        );
                    } else {
                        // On host: send file from client to server
                        await connection.query({
                            sql: `LOAD DATA LOCAL INFILE ? INTO TABLE \`${table}\``,
                            values: [csvPathPw],
                            infileStreamFactory: () => fs.createReadStream(csvPathPw),
                        } as any);
                    }
                }
            }
        }

        // Flush TYPO3 caches in both databases to prevent stale data after restore.
        // Without this, modules like Publish Files see cached comparison results
        // from before the restore instead of the freshly restored state.
        for (const db of ['local', 'foreign']) {
            await connection.query(`USE \`${db}\``);
            const [cacheRows] = await connection.query("SHOW TABLES LIKE 'cache_%'") as any;
            for (const row of cacheRows) {
                const tableName = Object.values(row)[0] as string;
                await connection.query(`TRUNCATE TABLE \`${tableName}\``);
            }
        }

        console.log('[direct-restore] Database restore complete.');
    } finally {
        await connection.end();
    }
}

/**
 * Restore fileadmin directories for both local and foreign instances.
 * Removes the current fileadmin and copies from backup (mirrors rsync --delete).
 * Also clears TYPO3 filesystem caches (var/cache/) on both instances to prevent
 * stale compiled data from interfering with the freshly restored state.
 */
export function restoreFileadmin(): void {
    console.log('[direct-restore] Restoring fileadmin...');
    try {
        // Remove current fileadmin contents and copy from backup
        execSync(`rm -rf "${LOCAL_FILEADMIN}"/* && cp -a "${FILEADMIN_BACKUP}/local/." "${LOCAL_FILEADMIN}/"`, {
            stdio: 'inherit', shell: '/bin/bash',
        });
        execSync(`rm -rf "${FOREIGN_FILEADMIN}"/* && cp -a "${FILEADMIN_BACKUP}/foreign/." "${FOREIGN_FILEADMIN}/"`, {
            stdio: 'inherit', shell: '/bin/bash',
        });

        // Clear TYPO3 filesystem caches (data/ subdirectories only - keep code/ and di/ intact
        // as those don't change between tests and are expensive to rebuild)
        for (const cacheDir of [LOCAL_VAR_CACHE, FOREIGN_VAR_CACHE]) {
            const dataDir = path.join(cacheDir, 'data');
            if (fs.existsSync(dataDir)) {
                execSync(`rm -rf "${dataDir}"/*`, { stdio: 'inherit', shell: '/bin/bash' });
            }
        }

        console.log('[direct-restore] Fileadmin restore complete.');
    } catch (error) {
        console.error('[direct-restore] Fileadmin restore failed:', error);
        throw error;
    }
}

/**
 * Full restore: databases + fileadmin.
 * Equivalent to `make restore`.
 */
export async function fullRestore(): Promise<void> {
    await restoreDatabases();
    restoreFileadmin();
}
