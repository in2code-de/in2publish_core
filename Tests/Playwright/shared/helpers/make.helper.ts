import { execSync } from 'child_process';

/**
 * Run a Makefile target from the extension root.
 *
 * The Playwright container's working directory is the extension root where the
 * Makefile lives (and the Docker CLI + socket are available), so no explicit
 * path is needed. Output is streamed to the test log; a non-zero exit throws.
 *
 * Examples: execMake('restore'), execMake('workflow-published').
 */
export function execMake(target: string): void {
  execSync(`make ${target}`, { stdio: 'inherit' });
}
