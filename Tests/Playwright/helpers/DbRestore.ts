import * as fs from 'fs';
import * as path from 'path';
import mysql, { Connection } from 'mysql2/promise';

/**
 * Layer name → absolute path of the dump directory.
 *
 * Each layer contains local/ and foreign/ subdirectories
 * with a _preamble.sql and per-table CSV files (mysql-loader format).
 */
const LAYER_DIRS: Record<string, string> = {
    base: path.resolve(__dirname, '../../../.project/data/dumps'),
};

const DB_CONFIG = {
    host: process.env.PLAYWRIGHT_DB_HOST ?? '127.0.0.1',
    port: parseInt(process.env.PLAYWRIGHT_DB_PORT ?? '3306', 10),
    user: process.env.DB_USER ?? 'root',
    password: process.env.DB_PASS ?? 'root',
    multipleStatements: true,
    localInfile: true,
} as const;

/**
 * Restores local and foreign databases from the given dump layers.
 *
 * Layers are applied in order. Each layer drops/recreates its tables via
 * _preamble.sql and loads data from per-table CSV files using LOAD DATA LOCAL INFILE.
 *
 * Requires the MySQL server to be started with --local-infile=1.
 *
 * Used for mid-run DB resets (e.g. uid-clash tests that need a clean DB per test).
 * For the initial clean state before the entire test run, see `make playwright` in
 * the root Makefile which calls `make restore` on the host.
 *
 * @param layers - Layers to apply in order (default: ['base'] for in2publish_core tests)
 */
export async function restoreDatabases(layers: string[] = ['base']): Promise<void> {
    for (const db of ['local', 'foreign'] as const) {
        const connection = await mysql.createConnection({ ...DB_CONFIG, database: db });
        try {
            for (const layer of layers) {
                const layerDir = LAYER_DIRS[layer];
                if (!layerDir) {
                    throw new Error(`Unknown layer: "${layer}". Available: ${Object.keys(LAYER_DIRS).join(', ')}`);
                }
                await restoreLayer(connection, path.join(layerDir, db));
            }
        } finally {
            await connection.end();
        }
    }
}

async function restoreLayer(connection: Connection, dumpDir: string): Promise<void> {
    const preamblePath = path.join(dumpDir, '_preamble.sql');
    if (!fs.existsSync(preamblePath)) {
        return;
    }

    const preamble = fs.readFileSync(preamblePath, 'utf8').trim();
    if (preamble) {
        await connection.query(preamble);
    }

    const csvFiles = fs.readdirSync(dumpDir).filter(f => f.endsWith('.csv'));
    for (const csvFile of csvFiles) {
        const tableName = path.basename(csvFile, '.csv');
        const csvPath = path.join(dumpDir, csvFile);
        await connection.query(
            `LOAD DATA LOCAL INFILE ${connection.escape(csvPath)} INTO TABLE \`${tableName}\``
        );
    }
}