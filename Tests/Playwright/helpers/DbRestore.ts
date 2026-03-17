import * as mysql2 from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Restores local and foreign databases directly from SQL dump files via mysql2.
 *
 * This helper connects to the mysql Docker container via network (hostname 'mysql'),
 * which works from within the Playwright Docker container without needing docker CLI.
 *
 * Used for mid-run DB resets (e.g. uid-clash tests that need a clean DB per test).
 * For the initial clean state before the entire test run, see `make playwright` in
 * the root Makefile which calls `make restore` on the host.
 */

const DUMPS_DIR = path.resolve(__dirname, '../../../.project/data/dumps');
const DB_HOST = process.env.PLAYWRIGHT_DB_HOST || 'mysql';
const DB_PORT = parseInt(process.env.PLAYWRIGHT_DB_PORT || '3306', 10);
const DB_USER = 'app';
const DB_PASS = 'app';

async function restoreDb(database: 'local' | 'foreign', sqlFile: string): Promise<void> {
    const conn = await mysql2.createConnection({
        host: DB_HOST,
        port: DB_PORT,
        user: DB_USER,
        password: DB_PASS,
        database,
        multipleStatements: true,
    });
    try {
        const sql = fs.readFileSync(sqlFile, 'utf8');
        await conn.query(sql);
    } finally {
        await conn.end();
    }
}

/**
 * Restore both local and foreign databases from their SQL dump files.
 * Works from inside the Playwright Docker container.
 */
export async function restoreDatabases(): Promise<void> {
    await restoreDb('local', path.join(DUMPS_DIR, 'db_local.sql'));
    await restoreDb('foreign', path.join(DUMPS_DIR, 'db_foreign.sql'));
}
