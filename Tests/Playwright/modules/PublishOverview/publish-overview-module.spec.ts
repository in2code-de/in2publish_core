import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';
import { restoreDatabases } from '../../helpers/direct-restore';

test.describe('Publish Overview Module', () => {

  test.beforeAll(async () => {
    await restoreDatabases();
  });

  test('Publish Overview module can be opened', async ({ page, backend }) => {
    await test.step('Given I am logged in and on the backend home page', async () => {
      await backend.login(config.local.baseUrl);
    });
    await backend.gotoModule('Publish Overview');
    await backend.searchInPageTreeAndSelectFirstOccurrence('EXT:in2publish_core');

    await expect(
        backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
    ).toBeVisible({ timeout: 10000 });
  });
});
