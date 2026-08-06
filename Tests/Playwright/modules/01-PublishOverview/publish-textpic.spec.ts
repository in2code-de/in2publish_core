import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';

test.describe('Publish Textpic', () => {

    /**
     * Textpic content element 16 has a different file reference on local and foreign.
     */
    test('Textpic with file reference can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('And I navigate to the page containing the changed textpic', async () => {
            await backend.gotoModule('Page');
            // Textpic 16 is on the second of the two pages named "News Folder" (page uid 26).
            await backend.searchInPageTreeAndSelectOccurrence('News Folder', 1);
        });

        await test.step('When I open "Publish Overview" and inspect the record', async () => {
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            // Publish Overview groups the changed content and its file relation below page uid 26.
            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-26"]');
            await expect(recordRow).toBeVisible();

            // Expand dirty properties
            const infoIcon = recordRow.locator('[data-action="opendirtypropertieslistcontainer"]');
            await infoIcon.click();

            await expect(recordRow).toContainText('9b news about maxim berg');
            await expect(recordRow).toContainText('maxim-berg-9XunOfueKKI-unsplash.jpg');
        });

        await test.step('And I publish the record', async () => {
            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-26"]');
            const arrowRight = recordRow.locator('.icon-actions-arrow-right');
            await expect(arrowRight).toBeVisible();
            await arrowRight.click();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully'
            );
        });

        await test.step('Then the textpic with image should be available in the Foreign Backend', async () => {
            const foreignContext = await browser.newContext();
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Page');
            await foreignBackend.searchInPageTreeAndSelectOccurrence('News Folder', 1);

            const editButton = foreignBackend.contentFrame
                .locator('div[data-table="tt_content"][data-uid="16"] a[title="Edit"]')
                .first();
            await expect(editButton).toBeVisible();
            await editButton.click();

            await expect(foreignBackend.contentFrame.locator('body')).toContainText('9b news about maxim berg');
            await expect(foreignBackend.contentFrame.locator('body')).toContainText(
                'maxim-berg-9XunOfueKKI-unsplash.jpg'
            );

            await foreignContext.close();
        });
    });
});
