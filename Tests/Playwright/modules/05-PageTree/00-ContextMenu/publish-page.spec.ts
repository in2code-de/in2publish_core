import { test, expect } from '../../../fixtures/setup-fixtures';
import config from '../../../config';
import { execMake } from '../../../shared/helpers';

test.describe('Page Tree - Context Menu', () => {

    test.beforeAll(async () => {
        execMake('restore');
    });

    /**
     * Publish the selected page via PageTree ContextMenu
     */
    test('Publish the selected page via PageTree ContextMenu', async ({ page, backend }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I select page "1a Page properties - changed"', async () => {

            await backend.gotoModule('Layout');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1a Page properties - changed');

            await expect(
                backend.contentFrame.getByRole('heading', { name: '1a Page properties - changed' })
            ).toBeVisible({ timeout: 10000 });

            await backend.openContextMenuOfSelectedPageInPageTree();
            await backend.clickPageTreeContextMenuItem('Publish this page');
            await page.waitForTimeout(1000);

            await expect(page.locator('#alert-container')).toContainText('The page "1a Page properties - changed" has been published.');
        });
    });
});
