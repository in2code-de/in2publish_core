import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';

test.describe('Publish page with textpic', () => {

    /**
     * Test Case 1e: Textpic content element with file reference can be published.
     * Mirrors Tests/Browser/PublishTextpicTest.php
     */
    test('Textpic with file reference can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open "Publish Overview" and inspect the record', async () => {
            await backend.gotoModule('Publish Overview');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1e page with textpic');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            // Verify the page title and resolved file relation
            await expect(backend.contentFrame.locator('body')).toContainText('1e page with textpic');
            await expect(backend.contentFrame.locator('body')).toContainText(
                'pages [1014] / tt_content [9026] / sys_file [9009] / _file [1:/Testcases/1e_textpic/1e_textpic.jpg'
            );
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

        await test.step('Then the textpic with image should be visible in the Foreign Backend', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Page');
            await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('1e page with textpic');
            const image = foreignBackend.contentFrame.locator('img[src*="1e_textpic/1e_textpic.jpg"]');
            await expect(image).toBeVisible();

            await foreignContext.close();
        });
    });
});
