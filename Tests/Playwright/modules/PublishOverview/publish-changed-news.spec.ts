import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { Environment } from '../../helpers/Environment';

test.describe('Publish Changed News', () => {

    test.beforeAll(async () => {
        await Environment.reset();
    });

    /**
     * Test Case 1c: Changed news record with image can be published.
     * Mirrors Tests/Browser/PublishChangedNewsTest.php
     */
    test('Changed news record can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('And I navigate to the News Folder', async () => {
            await backend.gotoModule('Page');
            await backend.searchInPageTreeAndSelectFirstOccurrence('News Folder');
        });

        await test.step('When I open "Publish Overview" and inspect the changed record', async () => {
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-33"]');
            await expect(recordRow).toBeVisible();

            // Expand dirty properties
            const infoIcon = recordRow.locator('[data-action="opendirtypropertieslistcontainer"]');
            await infoIcon.click();

            // Verify the changed news content and file reference
            const pageContent = backend.contentFrame.locator('.in2publish-page__content');
            await expect(pageContent).toContainText('Content element with image - edited');
            await expect(pageContent).toContainText('1:/user_upload/roman-wimmers-STrq0wSBGIs-unsplash.jpg');
        });

        await test.step('And I publish the record', async () => {
            const arrowRight = backend.contentFrame.locator('.icon-actions-arrow-right');
            await expect(arrowRight).toBeVisible();
            await arrowRight.click();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully'
            );
        });

        await test.step('Then the changes should be visible in the Foreign Backend', async () => {
            const foreignContext = await browser.newContext();
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('List');
            await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('News Folder');

            await expect(
                foreignBackend.contentFrame.locator('body')
            ).toContainText('Content element with image - edited', { timeout: 10000 });

            await foreignContext.close();
        });
    });
});
