import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { execMake } from '../../shared/helpers';

test.describe('Publish Changed News', () => {
    test.beforeEach(async () => {
        execMake('restore');
    });

    /**
     * Test Case 1c: Changed news record with image can be published.
     * Mirrors Tests/Browser/PublishChangedNewsTest.php
     */
    test('Changed news record can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open "Publish Overview" and select the News Folder Core', async () => {
            await backend.gotoModule('Publish Overview');
            await backend.searchInPageTreeAndSelectFirstOccurrence('News Folder Core');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-33"]');

            // Expand dirty properties
            const infoIcon = recordRow.locator('[data-action="opendirtypropertieslistcontainer"]');
            await infoIcon.click();

            // Verify the new news record and its file reference are listed
            const pageContent = recordRow.locator('.in2publish-page__content');
            await expect(pageContent).toContainText('24 news with Category 1');
            await expect(pageContent).toContainText('1:/user_upload/roman-wimmers-STrq0wSBGIs-unsplash.jpg');
        });

        await test.step('And I publish the record', async () => {
            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-33"]');
            const arrowRight = recordRow.locator('.icon-actions-arrow-right');
            await expect(arrowRight).toBeVisible({ timeout: 10000 });
            await arrowRight.click();

            await backend.waitUntilPublishingFinished();
            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully',
                { timeout: 30000 }
            );
        });

        await test.step('Then the changes should be visible in the Foreign Backend', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('List');
            await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('News Folder Core');

            await expect(
                foreignBackend.contentFrame.locator('body')
            ).toContainText('24 news with Category 1', { timeout: 10000 });

            await foreignContext.close();
        });
    });
});
