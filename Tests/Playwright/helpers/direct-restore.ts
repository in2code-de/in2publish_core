import mysql from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';
import { execSync } from 'child_process';

/**
 * Direct database and fileadmin restore for Playwright tests.
 *
 * Works in both the monorepo Playwright container and the in2publish_core
 * standalone Docker stack. Paths are configured via environment variables;
 * falls back to monorepo-relative paths when env vars are absent.
 *
 * Environment Variables:
 * - DB_HOST: MySQL host (default: 'mysql')
 * - DB_PORT: MySQL port (default: 3306)
 * - SQLDUMPSDIR: MySQL LOAD DATA path prefix (default: '.project/data/dumps')
 * - DUMPS_DIR_PW: Filesystem path to dumps dir inside the container
 * - FILEADMIN_BACKUP: Filesystem path to fileadmin backup dir
 * - LOCAL_FILEADMIN: Filesystem path to local TYPO3 fileadmin dir
 * - FOREIGN_FILEADMIN: Filesystem path to foreign TYPO3 fileadmin dir
 * - LOCAL_VAR_CACHE: Filesystem path to local TYPO3 var/cache dir
 * - FOREIGN_VAR_CACHE: Filesystem path to foreign TYPO3 var/cache dir
 */

// Fallback: monorepo root (helpers/ → Playwright/ → Tests/ → in2publish_core/ → packages/ → root)
const MONOREPO_ROOT = path.resolve(__dirname, '../../../../..');

const DUMPS_DIR_PW = process.env.DUMPS_DIR_PW ?? path.join(MONOREPO_ROOT, '.project/data/dumps');
// MySQL LOAD DATA INFILE path: volume mount maps .project/data/dumps to /.project/data/dumps
const SQLDUMPSDIR = process.env.SQLDUMPSDIR || '.project/data/dumps';
const DUMPS_DIR_MYSQL = '/' + SQLDUMPSDIR.replace(/^\.\//, '').replace(/\/$/, '');

const FILEADMIN_BACKUP = process.env.FILEADMIN_BACKUP ?? path.join(MONOREPO_ROOT, '.project/data/fileadmin');
const LOCAL_FILEADMIN = process.env.LOCAL_FILEADMIN ?? path.join(MONOREPO_ROOT, 'app/local/public/fileadmin');
const FOREIGN_FILEADMIN = process.env.FOREIGN_FILEADMIN ?? path.join(MONOREPO_ROOT, 'app/foreign/public/fileadmin');
const LOCAL_VAR_CACHE = process.env.LOCAL_VAR_CACHE ?? path.join(MONOREPO_ROOT, 'app/local/var/cache');
const FOREIGN_VAR_CACHE = process.env.FOREIGN_VAR_CACHE ?? path.join(MONOREPO_ROOT, 'app/foreign/var/cache');

const DB_HOST = process.env.DB_HOST || 'mysql';
const DB_PORT = parseInt(process.env.DB_PORT || '3306', 10);

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
    });

    console.log(`[direct-restore] Connecting to MySQL at ${DB_HOST}:${DB_PORT}`);

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

            // For each table with a CSV, truncate and import via LOAD DATA INFILE
            for (const table of tables) {
                const csvPathPw = path.join(DUMPS_DIR_PW, db, `${table}.csv`);
                if (fs.existsSync(csvPathPw)) {
                    await connection.query(`TRUNCATE TABLE \`${table}\``);
                    const csvPathMysql = `${DUMPS_DIR_MYSQL}/${db}/${table}.csv`;
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
 * Also clears TYPO3 filesystem caches (var/cache/data/) on both instances to prevent
 * stale compiled data from interfering with the freshly restored state.
 */
export function restoreFileadmin(): void {
    console.log('[direct-restore] Restoring fileadmin...');
    try {
        execSync(`rm -rf "${LOCAL_FILEADMIN}"/* && cp -a "${FILEADMIN_BACKUP}/local/." "${LOCAL_FILEADMIN}/"`, {
            stdio: 'inherit', shell: '/bin/bash',
        });
        execSync(`rm -rf "${FOREIGN_FILEADMIN}"/* && cp -a "${FILEADMIN_BACKUP}/foreign/." "${FOREIGN_FILEADMIN}/"`, {
            stdio: 'inherit', shell: '/bin/bash',
        });

        // Clear TYPO3 filesystem caches (data/ only — keep code/ and di/ intact
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