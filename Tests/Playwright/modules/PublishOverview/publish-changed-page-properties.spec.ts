import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { Environment } from '../../helpers/Environment';

test.describe('Publish Changed Page Properties', () => {

    test.beforeAll(async () => {
        await Environment.reset();
    });

    /**
     * Test Case 1a: Changed page properties can be published.
     * Mirrors Tests/Browser/PublishChangedPagePropertiesTest.php
     */
    test('Changed page properties can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('And I navigate to the changed page in the page tree', async () => {
            await backend.gotoModule('Page');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1a Page properties - changed');
        });

        await test.step('When I open "Publish Overview" and inspect the changed record', async () => {
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            // Locate the record row for pages-5
            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-5"]');
            await expect(recordRow).toBeVisible();

            // Click the info icon to expand dirty properties
            const infoIcon = recordRow.locator('[data-action="opendirtypropertieslistcontainer"]');
            await infoIcon.click();

            // Verify the changed page title is listed in the dirty properties
            await expect(backend.contentFrame.locator('body')).toContainText('Page title 1a Page properties - changed');
        });

        await test.step('And I publish the record', async () => {
            const arrowRight = backend.contentFrame.locator('.icon-actions-arrow-right');
            await expect(arrowRight).toBeVisible();
            await arrowRight.click();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully'
            );
        });

        await test.step('Then the changed title should be visible in the Foreign Backend', async () => {
            const foreignContext = await browser.newContext();
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Page');
            await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('1a Page properties - changed');

            await foreignPage.waitForTimeout(2000);

            // Click the Edit button for the page
            const editButton = foreignBackend.contentFrame.locator('a[title="Edit"]').first();
            await expect(editButton).toBeVisible();
            await editButton.click();

            // Verify the page title input contains the changed value
            const titleInput = foreignBackend.contentFrame.locator(
                '[data-formengine-input-name="data[pages][5][title]"]'
            );
            await expect(titleInput).toBeVisible({ timeout: 10000 });
            await expect(titleInput).toHaveValue(/1a Page properties - changed/);

            await foreignContext.close();
        });
    });
});
