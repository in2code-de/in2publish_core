import { execFileSync } from 'child_process';

/**
 * Run a Makefile target from the extension root.
 *
 * The Playwright container's working directory is the extension root where the Makefile lives (and the Docker CLI +
 * socket are available), so no explicit path is needed. Stdout is streamed to the test log, while stderr is captured
 * so that a failing target reports why it failed instead of just its exit code.
 *
 * Examples: execMake('restore'), execMake('workflow-published').
 */
export function execMake(target: string): void {
  try {
    execFileSync('make', [target], { stdio: ['ignore', 'inherit', 'pipe'], encoding: 'utf-8' });
  } catch (error) {
    const stderr = (error as { stderr?: string }).stderr ?? '';
    throw new Error(`make ${target} failed:${stderr === '' ? ' no error output' : `\n${stderr}`}`);
  }
}
