import { test, expect } from './fixtures/setup-fixtures';
import config from './config';

test.describe('Publish Overview Module', () => {
  test('Publish Overview module can be opened', async ({ page, backend }) => {
    await test.step('Navigate to backend', async () => {
      await page.goto(config.local.baseUrl);

      // Verify we're logged in
      await expect(page.locator('.scaffold-header')).toBeVisible({ timeout: 5000 });
    });

    await test.step('Navigate to Page module and select Home', async () => {
      await backend.gotoModule('Page');

      // Select Home in the page tree
      await backend.searchInPageTreeAndSelectFirstOccurrence('Home');
    });

    await test.step('Navigate to Publish Overview module', async () => {
      await backend.gotoModule('Publish Overview');
    });

    await test.step('Verify module is loaded', async () => {
      // Verify we're in the Publish Overview module
      await expect(
        backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
      ).toBeVisible({ timeout: 10000 });
    });
  });
});
