import { test, expect } from './fixtures/setup-fixtures';
import config from './config';

test.describe('Content Publisher Tests', () => {
  test.beforeEach(async ({ page, backend }) => {
    // For now, navigate directly to avoid storage state issues
    await page.goto(config.baseUrl);

    // Storage state should handle auth, but verify we're logged in
    const isLoggedIn = await page.locator('.scaffold-header')
      .isVisible({ timeout: 5000 })
      .catch(() => false);

    if (!isLoggedIn) {
      // If not logged in, login manually (storage state didn't work)
      await page.getByLabel('Username').fill(config.login.admin.username);
      await page.getByLabel('Password').fill(config.login.admin.password);
      await page.getByRole('button', { name: 'Login' }).click();
      await page.waitForLoadState('networkidle');
      await expect(page.locator('.scaffold-header')).toBeVisible();
    }

    // Navigate to Publisher Tools module
    await backend.gotoModule('Publisher Tools');
  });

  test('Tests View should show no errors', async ({ backend }) => {
    await test.step('Navigate to Tests tab', async () => {
      // Wait for the Tests tab to be visible and click it
      // The tab text has leading/trailing whitespace, so use a flexible regex
      const testsTab = backend.contentFrame
        .locator('a, button')
        .filter({ hasText: /^\s*Tests\s*$/ });

      await expect(testsTab).toBeVisible({ timeout: 10000 });
      await testsTab.click();
    });

    await test.step('Wait for tests to complete', async () => {
      // Wait for success callout to appear after running tests
      await expect(
        backend.contentFrame.locator('.callout-success').first()
      ).toBeVisible({ timeout: 30000 });
    });

    await test.step('Verify test results', async () => {
      const successCount = await backend.contentFrame
        .locator('.callout-success')
        .count();
      const warningCount = await backend.contentFrame
        .locator('.callout-warning')
        .count();
      const errorCount = await backend.contentFrame
        .locator('.callout-danger')
        .count();

      expect(successCount, 'Should have successful tests').toBeGreaterThan(10);
      expect(warningCount, 'Should have no warnings').toBe(0);
      expect(errorCount, 'Should have no errors').toBe(0);
    });
  });
});

