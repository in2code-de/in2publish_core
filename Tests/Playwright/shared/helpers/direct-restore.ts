import * as mysql from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';
import { execSync } from 'child_process';

const MONOREPO_ROOT = path.resolve(__dirname, '../../../../../..');

const DUMPS_DIR = process.env.DUMPS_DIR ?? path.join(MONOREPO_ROOT, '.project/data/dumps');
const FILEADMIN_DIR = process.env.FILEADMIN_DIR ?? path.join(MONOREPO_ROOT, '.project/data/fileadmin');
const LOCAL_FILEADMIN_DIR = process.env.LOCAL_FILEADMIN_DIR ?? path.join(MONOREPO_ROOT, 'app/local/public/fileadmin');
const FOREIGN_FILEADMIN_DIR = process.env.FOREIGN_FILEADMIN_DIR ?? path.join(MONOREPO_ROOT, 'app/foreign/public/fileadmin');
const LOCAL_CACHE_DIR = process.env.LOCAL_CACHE_DIR ?? path.join(MONOREPO_ROOT, 'app/local/var/cache');
const FOREIGN_CACHE_DIR = process.env.FOREIGN_CACHE_DIR ?? path.join(MONOREPO_ROOT, 'app/foreign/var/cache');

const DB_HOST = process.env.DB_HOST || 'mysql';
const DB_PORT = parseInt(process.env.DB_PORT || '3306', 10);

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

      const preamblePath = path.join(DUMPS_DIR, db, '_preamble.sql');
      const preamble = fs.readFileSync(preamblePath, 'utf8').trim();

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
          const csvPathMysql = `${DUMPS_DIR}/${db}/${table}.csv`;
          await connection.query(`LOAD DATA INFILE '${csvPathMysql}' INTO TABLE \`${table}\``);
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

    console.log('[direct-restore] Database restore complete.');
  } finally {
    await connection.end();
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
