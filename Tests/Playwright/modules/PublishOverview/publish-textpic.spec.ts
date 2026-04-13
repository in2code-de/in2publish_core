import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { fullRestore } from '../../helpers/direct-restore';

test.describe('Publish Textpic', () => {

    test.beforeAll(async () => {
        await fullRestore();
    });

    /**
     * Test Case 1e: Textpic content element with file reference can be published.
     * Mirrors Tests/Browser/PublishTextpicTest.php
     *
     * @todo Page '1e Page with textpic' (expected uid=79) does not exist in the current DB dump.
     *       Either add the page/content to the DB dump or update this test once the dump is updated.
     */
    test.skip('Textpic with file reference can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open "Publish Overview" and inspect the record', async () => {
            await backend.gotoModule('Publish Overview');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1e Page with textpic');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-79"]');
            await expect(recordRow).toBeVisible();

            // Expand dirty properties
            const infoIcon = recordRow.locator('[data-action="opendirtypropertieslistcontainer"]');
            await infoIcon.click();

            // Verify the page title and resolved file relation
            await expect(backend.contentFrame.locator('body')).toContainText('1e Page with textpic');
            await expect(backend.contentFrame.locator('body')).toContainText(
                'pages [79] / sys_file_reference [11] / sys_file [5] / _file [1:/user_upload/maxim-berg-9XunOfueKKI-unsplash.jpg]'
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
            await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('1e Page with textpic');

            // Verify the image preview exists in the Page module
            const previewElement = foreignBackend.contentFrame.locator('.preview-thumbnails-element');
            await expect(previewElement).toBeVisible({ timeout: 10000 });

            const image = previewElement.locator('img[alt="maxim-berg-9XunOfueKKI-unsplash.jpg"]');
            await expect(image).toBeVisible();

            await foreignContext.close();
        });
    });
});
