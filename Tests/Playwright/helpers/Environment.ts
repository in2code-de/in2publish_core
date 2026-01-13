import { execSync } from 'child_process';
import path from 'path';

export class Environment {
    /**
     * Resets the environment by running make targets from the project root.
     * Replicates the setup from AbstractBrowserTestCase.php.
     *
     * Note: When running in Docker (CI=1), the reset is skipped as make is not available
     * in the Playwright container. In Docker mode, ensure the environment is reset
     * before running tests (e.g. DB reset in .github/workflows/tests.yml.
     */
    static async reset() {
        if (process.env.CI === '1') {
            console.log('Skipping environment reset in CI/Docker mode (make not available in container).');
            console.log('Ensure you have run "make restore" on the host before running tests.');
            return;
        }

        // Since we run 'npx playwright test' from the package root, process.cwd() is safe
        const packageRoot = process.cwd();

        try {
            console.log('Resetting environment (database and fileadmin)...');
            // Using 'restore' target from in2publish_core Makefile
            execSync('make restore', {
                cwd: packageRoot,
                stdio: 'inherit'
            });
            console.log('Environment reset complete.');
        } catch (error) {
            console.error('Failed to reset environment:', error);
            throw error;
        }
    }
}
