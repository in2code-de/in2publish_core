import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';
import { restoreDatabases } from '../../helpers/direct-restore';

test.describe('Publish Overview Module', () => {

  test.beforeAll(async () => {
    await restoreDatabases();
  });

  test('Publish Overview module can be opened', async ({ page, backend }) => {
    await test.step('Given I am logged in and on the backend home page', async () => {
      await backend.login();
    });

    await test.step('When I open "Publish Overview" for the Home page', async () => {
      // Use direct URL navigation (page id=65) to avoid page tree race conditions.
      // Page 65 = "1b.1 Page content - changed" (a known accessible page in test fixtures)
      await backend.gotoModuleWithPageId('module/in2publish_core/m1', 65);
    });

    await test.step('Then I should see the module content loaded', async () => {
      await expect(
        backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
      ).toBeVisible({ timeout: 15000 });
    });
  });
});
