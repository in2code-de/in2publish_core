import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright Configuration for in2publish_core
 *
 * This configuration runs in the Playwright Docker service.
 *
 * Environment Variables:
 * - PLAYWRIGHT_BASE_URL: Override local instance URL (default: https://local.v13.in2publish-core.de/typo3/)
 * - PLAYWRIGHT_FOREIGN_BASE_URL: Override foreign instance URL (default: https://foreign.v13.in2publish-core.de/typo3/)
 * - CI: Set to 1/true for CI mode (enables retries, enforces forbidOnly)
 *
 * Usage:
 *   Install: make playwright-install
 *   Test:    make playwright
 *   UI:      make playwright-ui
 *
 * See https://playwright.dev/docs/test-configuration.
 */
/* Treat the CI env var as a real flag: unset, empty and "0" all mean "not CI".
 * (docker-compose passes CI="0" by default, which is truthy as a JS string.) */
const isCi = !!process.env.CI && process.env.CI !== '0' && process.env.CI !== 'false';

export default defineConfig({
  testDir: './Tests/Playwright',
  /* Run tests in files in parallel */
  fullyParallel: false, // TYPO3 tests share database state
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: isCi,
  /* Retry on CI only */
  retries: isCi ? 2 : 0,
  /* Sequential execution for shared database */
  workers: 1,

  /* Test timeout */
  timeout: 180000,

  /* Expect timeout */
  expect: {
    timeout: 30000,
  },

  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html'],
    ['list'],
  ],

  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'https://local.v13.in2publish-core.de/typo3/',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'retain-on-failure',

    /* Capture screenshots on failure */
    screenshot: 'only-on-failure',

    /* Record video on failure */
    video: 'retain-on-failure',

    /* Ignore HTTPS errors for local development */
    ignoreHTTPSErrors: true,

    actionTimeout: 30000,
    navigationTimeout: 60000,
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'setup',
      testMatch: /global\.setup\.ts/,
      use: {
        // Setup project should not use storageState
        storageState: undefined,
      },
    },
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        // Use saved authentication state for tests
        storageState: 'Tests/Playwright/.auth/login.json',
      },
      dependencies: ['setup'],
    },
  ],
});
