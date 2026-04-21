import { execSync } from 'child_process';
import { EnvironmentResetOptions } from '../types';

export function createEnvironment(options: EnvironmentResetOptions) {
  return {
    async reset(): Promise<void> {
      const skipInCi = options.skipInCi ?? true;

      if (skipInCi && process.env.CI === '1') {
        console.log('Skipping environment reset in CI/Docker mode.');
        console.log(`Ensure you have run "${options.command}" on the host before running tests.`);
        return;
      }

      const cwd = options.cwd || process.cwd();

      try {
        console.log(`Resetting environment (${options.command})...`);
        execSync(options.command, { cwd, stdio: 'inherit' });
        console.log('Environment reset complete.');
      } catch (error) {
        console.error('Failed to reset environment:', error);
        throw error;
      }
    },
  };
}
