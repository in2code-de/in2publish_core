import mysql from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';
import { execSync } from 'child_process';

/**
 * Direct database and fileadmin restore from the Playwright container.
 *
 * Database: Connects to MySQL and re-imports dump data using LOAD DATA INFILE.
 * Fileadmin: Restores fileadmin from backup to both instances.
 *
 * Supports two contexts (auto-detected):
 * - Monorepo: Playwright at /work/packages/in2publish_core, app dirs at /work/app/{local,foreign}/
 * - Standalone: Playwright at /work, app dirs at /work/Build/{local,foreign}/
 *
 * MySQL LOAD DATA path is derived from SQLDUMPSDIR env var (set in .env).
 */
// in2publish_core package root (3 levels up from Tests/Playwright/helpers/)
const PACKAGE_ROOT = path.resolve(__dirname, '../../..');

// Detect monorepo vs standalone context:
// In monorepo, PACKAGE_ROOT is /work/packages/in2publish_core and /work/app/ exists.
// In standalone, PACKAGE_ROOT is /work and Build/local/ exists instead.
const isMonorepo = fs.existsSync(path.resolve(PACKAGE_ROOT, '../../app'));
const APP_ROOT = isMonorepo ? path.resolve(PACKAGE_ROOT, '../..') : PACKAGE_ROOT;
const APP_PREFIX = isMonorepo ? 'app' : 'Build';

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

/**
 * Restore both local and foreign databases from dump files.
 */
export async function restoreDatabases(): Promise<void> {
    const connection = await mysql.createConnection({
        host: 'mysql',
        port: 3306,
        user: 'root',
        password: 'root',
        multipleStatements: true,
    });

    try {
        for (const db of ['local', 'foreign']) {
            console.log(`[direct-restore] Restoring ${db} database...`);
            await connection.query(`USE \`${db}\``);

            // Execute preamble (DROP TABLE IF EXISTS + CREATE TABLE statements)
            const preamblePath = path.join(DUMPS_DIR_PW, db, '_preamble.sql');
            const preamble = fs.readFileSync(preamblePath, 'utf8').trim();
            if (preamble) {
                await connection.query(preamble);
            }

            // Get list of tables
            const [rows] = await connection.query('SHOW TABLES') as any;
            const tables = rows.map((row: any) => Object.values(row)[0] as string);

            // For each table with a CSV, truncate and import
            for (const table of tables) {
                const csvPathPw = path.join(DUMPS_DIR_PW, db, `${table}.csv`);
                if (fs.existsSync(csvPathPw)) {
                    const csvPathMysql = `${DUMPS_DIR_MYSQL}/${db}/${table}.csv`;
                    await connection.query(`TRUNCATE TABLE \`${table}\``);
                    await connection.query(
                        `LOAD DATA INFILE '${csvPathMysql}' INTO TABLE \`${table}\``
                    );
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
