import * as mysql2 from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Restores local and foreign databases from CSV dumps via LOAD DATA INFILE.
 *
 * This helper connects to the mysql Docker container via network (hostname 'mysql').
 * It uses server-side LOAD DATA INFILE (not LOCAL), which reads files from the MySQL
 * server's filesystem. The dump directory is mounted into the MySQL container at
 * /.project/data/dumps/ via the SQLDUMPSDIR docker-compose volume.
 *
 * Each database subdirectory contains:
 * - _preamble.sql: DROP TABLE, CREATE TABLE, TRUNCATE statements
 * - *.csv files: One per table, loaded via LOAD DATA INFILE
 *
 * Used for mid-run DB resets (e.g. uid-clash tests that need a clean DB per test).
 * For the initial clean state before the entire test run, see the `restore` Makefile
 * target which is a prerequisite of `playwright-docker`.
 */

// Path to dumps as seen from the Playwright container (for reading file lists)
const DUMPS_DIR = path.resolve(__dirname, '../../../.project/data/dumps');
// Path to dumps as seen from the MySQL server container (for LOAD DATA INFILE)
// Must match the SQLDUMPSDIR volume mount: ${SQLDUMPSDIR}:/${SQLDUMPSDIR}
const MYSQL_DUMPS_DIR = '/packages/in2publish_core/.project/data/dumps';

const DB_HOST = process.env.DB_HOST || 'mysql';
const DB_USER = process.env.DB_USER || 'root';
const DB_PASS = process.env.DB_PASS || 'root';

async function restoreDb(database: 'local' | 'foreign'): Promise<void> {
    const dbDir = path.join(DUMPS_DIR, database);
    const mysqlDbDir = `${MYSQL_DUMPS_DIR}/${database}`;

    const conn = await mysql2.createConnection({
        host: DB_HOST,
        user: DB_USER,
        password: DB_PASS,
        database,
        multipleStatements: true,
    });

    try {
        // Execute preamble (DROP/CREATE/TRUNCATE statements)
        const preamble = fs.readFileSync(path.join(dbDir, '_preamble.sql'), 'utf8');
        await conn.query(preamble);

        // Load each CSV file via LOAD DATA INFILE (server-side)
        const csvFiles = fs.readdirSync(dbDir).filter(f => f.endsWith('.csv'));
        for (const csvFile of csvFiles) {
            const tableName = path.basename(csvFile, '.csv');
            const mysqlCsvPath = `${mysqlDbDir}/${csvFile}`;

            await conn.query(`TRUNCATE TABLE \`${tableName}\``);
            await conn.query(
                `LOAD DATA INFILE '${mysqlCsvPath}' INTO TABLE \`${tableName}\``,
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
