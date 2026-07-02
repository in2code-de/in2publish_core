import { execFileSync } from 'child_process';

/**
 * Run a shell command in a compose service via the mounted Docker socket and
 * return its trimmed stdout.
 *
 * The target container is addressed by its compose project and service name,
 * e.g. execInContainer('in2publish_core', 'local-php', 'composer install').
 * This works for any service in any running compose project, so no separate
 * per-container helper is required.
 */
export function execInContainer(composeProject: string, service: string, command: string): string {
  return execFileSync(
    'docker',
    ['compose', '-p', composeProject, 'exec', '-T', service, 'sh', '-lc', command],
    { encoding: 'utf-8', stdio: ['ignore', 'pipe', 'pipe'] },
  ).trim();
}

/**
 * Run a TYPO3 CLI command (`vendor/bin/typo3 <command>`) in the given compose
 * service, e.g. execTypo3Command('in2publish_core', 'local-php', 'cache:flush').
 */
export function execTypo3Command(composeProject: string, service: string, command: string): string {
  return execInContainer(composeProject, service, `vendor/bin/typo3 ${command}`);
}
