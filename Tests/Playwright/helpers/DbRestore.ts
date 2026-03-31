import * as mysql2 from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Restores local and foreign databases using the mysql-loader mechanism:
 * executes _preamble.sql (DROP/CREATE tables) then loads per-table CSV dumps via
 * LOAD DATA LOCAL INFILE — matching exactly what `make mysql-restore` does.
 *
 * Also provides fileadmin restore matching `make fileadmin-restore`.
 *
 * Used for mid-run DB resets (e.g. uid-clash tests that need a clean DB per test).
 * For the initial clean state before the entire test run, see `make playwright` in
 * the root Makefile which calls `make restore` on the host.
 */

const DUMPS_DIR = path.resolve(__dirname, '../../../.project/data/dumps');
const BUILD_DIR = path.resolve(__dirname, '../../../Build');
const FILEADMIN_DATA_DIR = path.resolve(__dirname, '../../../.project/data/fileadmin');

const DB_USER = 'root';
const DB_PASS = 'root';

/**
 * Returns the MySQL host and port appropriate for the current environment:
 * - Inside Docker: 'mysql' (Docker DNS) on port 3306
 * - Local (host): '127.0.0.1' on the SQLPORT exposed by docker-compose (read from .env)
 */
function getDbConnection(): { host: string; port: number } {
    // /.dockerenv is created by the Docker runtime inside every container
    if (fs.existsSync('/.dockerenv')) {
        return { host: 'mysql', port: 3306 };
    }
    const envFile = path.resolve(__dirname, '../../../.env');
    if (fs.existsSync(envFile)) {
        const match = fs.readFileSync(envFile, 'utf8').match(/^SQLPORT=(\d+)/m);
        if (match) {
            return { host: '127.0.0.1', port: parseInt(match[1], 10) };
        }
    }
    return { host: '127.0.0.1', port: 3306 };
}

async function mysqlLoaderImport(database: 'local' | 'foreign', dumpsFolder: string): Promise<void> {
    const { host, port } = getDbConnection();
    const conn = await mysql2.createConnection({
        host,
        port,
        user: DB_USER,
        password: DB_PASS,
        database,
        multipleStatements: true,
        infileStreamFactory: (filePath: string) => fs.createReadStream(filePath),
    });
    try {
        // Execute preamble: DROP TABLE IF EXISTS + CREATE TABLE statements
        const preambleFile = path.join(dumpsFolder, '_preamble.sql');
        const preamble = fs.readFileSync(preambleFile, 'utf8').trim();
        if (preamble) {
            await conn.query(preamble);
        }

        // List all tables present after the preamble ran
        const [rows] = await conn.query<mysql2.RowDataPacket[]>('SHOW TABLES');
        const tables = rows.map(row => Object.values(row)[0] as string);

        // For each table that has a CSV dump, truncate then load
        for (const table of tables) {
            const csvFile = path.join(dumpsFolder, `${table}.csv`);
            if (fs.existsSync(csvFile)) {
                await conn.query(`TRUNCATE TABLE \`${table}\``);
                await conn.query(`LOAD DATA LOCAL INFILE '${csvFile}' INTO TABLE \`${table}\``);
            }
        }
    } finally {
        await conn.end();
    }
}

/**
 * Restore both local and foreign databases using the mysql-loader CSV mechanism.
 * Matches the `make mysql-restore` target.
 */
export async function restoreDatabases(): Promise<void> {
    await mysqlLoaderImport('local', path.join(DUMPS_DIR, 'local'));
    await mysqlLoaderImport('foreign', path.join(DUMPS_DIR, 'foreign'));
}

/**
 * Restore local and foreign fileadmin directories from their data snapshots.
 * Matches the `make fileadmin-restore` target (rsync -a --delete).
 */
export function restoreFileadmin(): void {
    for (const instance of ['local', 'foreign'] as const) {
        const src = path.join(FILEADMIN_DATA_DIR, instance);
        const dest = path.join(BUILD_DIR, instance, 'public', 'fileadmin');
        fs.rmSync(dest, { recursive: true, force: true });
        fs.cpSync(src, dest, { recursive: true });
    }
}