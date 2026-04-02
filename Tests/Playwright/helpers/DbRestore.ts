import * as mysql2 from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Restores local and foreign databases from CSV dumps via LOAD DATA LOCAL INFILE.
 *
 * This helper connects to the mysql Docker container via network (hostname 'mysql'),
 * which works from within the Playwright Docker container without needing docker CLI.
 *
 * Each database subdirectory contains:
 * - _preamble.sql: DROP TABLE, CREATE TABLE, TRUNCATE statements
 * - *.csv files: One per table, loaded via LOAD DATA LOCAL INFILE
 *
 * Used for mid-run DB resets (e.g. uid-clash tests that need a clean DB per test).
 * For the initial clean state before the entire test run, see `make playwright` in
 * the root Makefile which calls `make restore` on the host.
 */

const DUMPS_DIR = path.resolve(__dirname, '../../../.project/data/dumps');
const DB_HOST = 'mysql';
const DB_USER = 'root';
const DB_PASS = 'root';

async function restoreDb(database: 'local' | 'foreign'): Promise<void> {
    const dbDir = path.join(DUMPS_DIR, database);
    const conn = await mysql2.createConnection({
        host: DB_HOST,
        user: DB_USER,
        password: DB_PASS,
        database,
        multipleStatements: true,
        localInfile: true,
    });

    try {
        // Execute preamble (DROP/CREATE/TRUNCATE statements)
        const preamble = fs.readFileSync(path.join(dbDir, '_preamble.sql'), 'utf8');
        await conn.query(preamble);

        // Load each CSV file via LOAD DATA LOCAL INFILE
        const csvFiles = fs.readdirSync(dbDir).filter(f => f.endsWith('.csv'));
        for (const csvFile of csvFiles) {
            const tableName = path.basename(csvFile, '.csv');
            const csvPath = path.join(dbDir, csvFile);

            await conn.query(`TRUNCATE TABLE \`${tableName}\``);
            await conn.query(
                `LOAD DATA LOCAL INFILE ? INTO TABLE \`${tableName}\``,
                [csvPath],
            );
        }
    } finally {
        await conn.end();
    }
}

/**
 * Restore both local and foreign databases from their CSV dump files.
 * Works from inside the Playwright Docker container.
 */
export async function restoreDatabases(): Promise<void> {
    await restoreDb('local');
    await restoreDb('foreign');
}
