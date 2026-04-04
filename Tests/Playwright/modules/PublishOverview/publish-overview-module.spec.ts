import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';

test.describe('Publish Overview Module', () => {
  test('Publish Overview module can be opened', async ({ page, backend }) => {
    await test.step('Given I am logged in and on the backend home page', async () => {
      await backend.login();
    });


    await test.step('When I open "Publish Overview" and select "Home"', async () => {
      await backend.gotoModule('Publish Overview');
      await backend.searchInPageTreeAndSelectFirstOccurrence('Home');
    });

    await test.step('Then I should see the module content loaded', async () => {
      await expect(
        backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
      ).toBeVisible({ timeout: 10000 });
    });
  });
});
