import { execSync } from 'child_process';
import path from 'path';

export class Environment {
    /**
     * Resets the environment by running make targets from the project root.
     * Replicates the setup from AbstractBrowserTestCase.php.
     */
    static async reset() {
        // Since we run 'npx playwright test' from the package root, process.cwd() is safe
        const packageRoot = process.cwd();

        // We use stdio: 'ignore' to prevent cluttering the test output, 
        // unless you want to debug the reset process.
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
