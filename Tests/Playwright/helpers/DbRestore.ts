import { execSync } from 'child_process';

/**
 * Restores local and foreign databases using the layered restore script.
 *
 * Calls restore-db.sh which uses mysql-loader to import CSV dumps layer by layer.
 * The base layer contains all shared TYPO3 + in2publish_core tables.
 * The in2publish layer adds workflow/notification tables (optional).
 *
 * Used for mid-run DB resets (e.g. uid-clash tests that need a clean DB per test).
 * For the initial clean state before the entire test run, see `make playwright` in
 * the root Makefile which calls `make restore` on the host.
 */

const RESTORE_SCRIPT = '/.project/scripts/restore-db.sh';

/**
 * Restore both local and foreign databases.
 * @param layers - Layers to apply (default: ['base'] for in2publish_core tests)
 */
export async function restoreDatabases(layers: string[] = ['base']): Promise<void> {
    const layerArgs = layers.join(' ');
    execSync(`${RESTORE_SCRIPT} local ${layerArgs}`, { stdio: 'pipe' });
    execSync(`${RESTORE_SCRIPT} foreign ${layerArgs}`, { stdio: 'pipe' });
}
