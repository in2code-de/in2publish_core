import { defineConfig, devices } from '@playwright/test';

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './Tests/Playwright',
  /* Run tests in files in parallel */
  fullyParallel: false, // TYPO3 tests share database state
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Sequential execution for shared database */
  workers: 1,

  /* Test timeout */
  timeout: 60000,

  /* Expect timeout */
  expect: {
    timeout: 10000,
  },

  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html'],
    ['list'],
  ],

  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'https://local.v13.in2publish.de/typo3/',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'retain-on-failure',

    /* Capture screenshots on failure */
    screenshot: 'only-on-failure',

    /* Record video on failure */
    video: 'retain-on-failure',

    /* Ignore HTTPS errors for local development */
    ignoreHTTPSErrors: true,
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
