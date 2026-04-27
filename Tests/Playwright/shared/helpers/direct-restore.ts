import * as mysql from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';
import { execSync } from 'child_process';

const MONOREPO_ROOT = path.resolve(__dirname, '../../../../../..');

function resolveFromRoot(value: string): string {
  return path.isAbsolute(value) ? value : path.join(MONOREPO_ROOT, value);
}

const DUMPS_DIR = resolveFromRoot(process.env.DUMPS_DIR ?? '.project/data/dumps');
const FILEADMIN_DIR = resolveFromRoot(process.env.FILEADMIN_DIR ?? '.project/data/fileadmin');
const LOCAL_FILEADMIN_DIR = resolveFromRoot(process.env.LOCAL_FILEADMIN_DIR ?? 'app/local/public/fileadmin');
const FOREIGN_FILEADMIN_DIR = resolveFromRoot(process.env.FOREIGN_FILEADMIN_DIR ?? 'app/foreign/public/fileadmin');
const LOCAL_CACHE_DIR = resolveFromRoot(process.env.LOCAL_CACHE_DIR ?? 'app/local/var/cache');
const FOREIGN_CACHE_DIR = resolveFromRoot(process.env.FOREIGN_CACHE_DIR ?? 'app/foreign/var/cache');
const FOREIGN_ONLY_EMPTY_TABLES_FILE = path.join(__dirname, 'foreign-only-empty-tables.txt');

const DB_HOST = process.env.DB_HOST || 'mysql';
const DB_PORT = parseInt(process.env.DB_PORT || '3306', 10);

// Keep in sync with Makefile - restore-dbs
// this truncates empty tables on foreign
// empty tables are omitted by the mysql-loader during dumping - so this is the only way to clear these tables
const FOREIGN_ONLY_EMPTY_TABLES = fs
  .readFileSync(FOREIGN_ONLY_EMPTY_TABLES_FILE, 'utf8')
  .split('\n')
  .map((line) => line.trim())
  .filter((line) => line !== '' && !line.startsWith('#'));

async function ensureForeignEmptyTablesExist(connection: mysql.Connection): Promise<void> {
  await connection.query('USE `local`');
  const [localRows] = await connection.query('SHOW TABLES') as any;
  const localTables = new Set(localRows.map((row: any) => Object.values(row)[0] as string));

  await connection.query('USE `foreign`');
  const [foreignRows] = await connection.query('SHOW TABLES') as any;
  const foreignTables = new Set(foreignRows.map((row: any) => Object.values(row)[0] as string));

  for (const table of FOREIGN_ONLY_EMPTY_TABLES) {
    if (!localTables.has(table)) {
      continue;
    }

    if (!foreignTables.has(table)) {
      await connection.query(`CREATE TABLE \`foreign\`.\`${table}\` LIKE \`local\`.\`${table}\``);
      foreignTables.add(table);
    }
  }
}

export async function restoreDatabases(): Promise<void> {
  for (const db of ['local', 'foreign']) {
    const dumpPath = path.join(DUMPS_DIR, db);
    const pagesDumpPath = path.join(dumpPath, 'pages.csv');

    if (!fs.existsSync(pagesDumpPath)) {
      throw new Error(
        `[direct-restore] Missing dump file ${pagesDumpPath}. ` +
        `DUMPS_DIR must point to the monorepo dump directory.`,
      );
    }
  }

  const adminConnection = await mysql.createConnection({
    host: DB_HOST,
    port: DB_PORT,
    user: 'root',
    password: 'root',
  });
  const [[localInfileRow]] = await adminConnection.query(
    "SHOW GLOBAL VARIABLES LIKE 'local_infile'",
  ) as any;
  const originalLocalInfile = String(localInfileRow?.Value ?? 'OFF').toUpperCase();
  await adminConnection.query('SET GLOBAL local_infile = 1');
  await adminConnection.end();

  const connection = await mysql.createConnection({
    host: DB_HOST,
    port: DB_PORT,
    user: 'root',
    password: 'root',
    multipleStatements: true,
    infileStreamFactory: (filePath: string) => fs.createReadStream(filePath),
  });

  console.log(`[direct-restore] Connecting to MySQL at ${DB_HOST}:${DB_PORT}`);

  try {
    await ensureForeignEmptyTablesExist(connection);

    for (const db of ['local', 'foreign']) {
      console.log(`[direct-restore] Restoring ${db} database...`);
      await connection.query(`USE \`${db}\``);

      const preamblePath = path.join(DUMPS_DIR, db, '_preamble.sql');
      const preamble = fs.existsSync(preamblePath)
        ? fs.readFileSync(preamblePath, 'utf8').trim()
        : '';

      if (preamble) {
        await connection.query("SET sql_mode = ''");
        await connection.query(preamble);
      }

      await connection.query(
        "ALTER TABLE `pages` MODIFY COLUMN `link` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL",
      ).catch(() => {});
      await connection.query(
        "ALTER TABLE `pages` MODIFY COLUMN `canonical_link` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL",
      ).catch(() => {});

      const [rows] = await connection.query('SHOW TABLES') as any;
      const tables = rows.map((row: any) => Object.values(row)[0] as string);

      for (const table of tables) {
        const csvPathPw = path.join(DUMPS_DIR, db, `${table}.csv`);

        if (fs.existsSync(csvPathPw)) {
          await connection.query(`TRUNCATE TABLE \`${table}\``);
          const csvPathMysql = `${DUMPS_DIR}/${db}/${table}.csv`.replace(/'/g, "\\'");
          await connection.query(`LOAD DATA LOCAL INFILE '${csvPathMysql}' INTO TABLE \`${table}\``);
        }
      }

      if (db === 'foreign') {
        for (const table of FOREIGN_ONLY_EMPTY_TABLES) {
          if (tables.includes(table)) {
            await connection.query(`TRUNCATE TABLE \`${table}\``);
          }
        }
      }
    }

    for (const db of ['local', 'foreign']) {
      await connection.query(`USE \`${db}\``);
      const [cacheRows] = await connection.query("SHOW TABLES LIKE 'cache_%'") as any;

      for (const row of cacheRows) {
        const tableName = Object.values(row)[0] as string;
        await connection.query(`TRUNCATE TABLE \`${tableName}\``);
      }
    }

    for (const db of ['local', 'foreign']) {
      await connection.query(`USE \`${db}\``);
      const [pageCountRows] = await connection.query('SELECT COUNT(*) AS c FROM `pages`') as any;
      const pageCount = Number(pageCountRows[0]?.c ?? 0);
      console.log(`[direct-restore] ${db}.pages row count after restore: ${pageCount}`);
      if (pageCount === 0) {
        throw new Error(
          `[direct-restore] Sanity check failed: ${db}.pages is empty after restore. ` +
          `Expected rows from ${DUMPS_DIR}/${db}/pages.csv.`,
        );
      }
    }

    console.log('[direct-restore] Database restore complete.');
  } finally {
    await connection.end();

    if (originalLocalInfile !== 'ON') {
      const resetConnection = await mysql.createConnection({
        host: DB_HOST,
        port: DB_PORT,
        user: 'root',
        password: 'root',
      });
      await resetConnection.query('SET GLOBAL local_infile = 0');
      await resetConnection.end();
    }
  }
}

export async function executeLocalSql(sql: string): Promise<void> {
  const connection = await mysql.createConnection({
    host: DB_HOST,
    port: DB_PORT,
    user: 'root',
    password: 'root',
    multipleStatements: true,
  });

  try {
    await connection.query('USE `local`');
    await connection.query(sql);
  } finally {
    await connection.end();
  }
}

export function restoreFileadmin(): void {
  console.log('[direct-restore] Restoring fileadmin...');

  try {
    for (const source of [path.join(FILEADMIN_DIR, 'local'), path.join(FILEADMIN_DIR, 'foreign')]) {
      if (!fs.existsSync(source)) {
        throw new Error(
          `[direct-restore] Missing fileadmin source ${source}. ` +
          `FILEADMIN_DIR must point to the monorepo fileadmin directory.`,
        );
      }
    }

    execSync(`rm -rf "${LOCAL_FILEADMIN_DIR}"/* && cp -a "${FILEADMIN_DIR}/local/." "${LOCAL_FILEADMIN_DIR}/"`, {
      stdio: 'inherit',
      shell: '/bin/bash',
    });
    execSync(`rm -rf "${FOREIGN_FILEADMIN_DIR}"/* && cp -a "${FILEADMIN_DIR}/foreign/." "${FOREIGN_FILEADMIN_DIR}/"`, {
      stdio: 'inherit',
      shell: '/bin/bash',
    });

    for (const cacheDir of [LOCAL_CACHE_DIR, FOREIGN_CACHE_DIR]) {
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

export async function fullRestore(): Promise<void> {
  await restoreDatabases();
  restoreFileadmin();
}
