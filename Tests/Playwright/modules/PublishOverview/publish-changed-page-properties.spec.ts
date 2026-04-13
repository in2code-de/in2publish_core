import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { fullRestore } from '../../helpers/direct-restore';

test.describe('Publish Changed Page Properties', () => {

    test.beforeAll(async () => {
        await fullRestore();
    });

    /**
     * Test Case 1a: Changed page properties can be published.
     * Mirrors Tests/Browser/PublishChangedPagePropertiesTest.php
     */
    test('Changed page properties can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open "Publish Overview" and select the changed page', async () => {
            await backend.gotoModule('Publish Overview');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1a Page properties - changed');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            // Verify the changed page title is shown
            await expect(backend.contentFrame.locator('body')).toContainText('1a Page properties - changed');
        });

        await test.step('And I publish the record', async () => {
            const arrowRight = backend.contentFrame.locator('.icon-actions-arrow-right');
            await expect(arrowRight).toBeVisible();
            await arrowRight.click();

            await backend.waitUntilPublishingFinished();
            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully',
                { timeout: 30000 }
            );
        });

        await test.step('Then the changed title should be visible in the Foreign Backend', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Page');
            await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('1a Page properties - changed');

            await foreignPage.waitForTimeout(2000);

            // Verify the changed page title is shown
            await expect(foreignBackend.contentFrame.locator('body')).toContainText('1a Page properties - changed');

            await foreignContext.close();
        });
    });
});
