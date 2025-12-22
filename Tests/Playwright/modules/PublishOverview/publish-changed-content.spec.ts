import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { Environment } from '../../helpers/Environment';

test.describe('Publish Changed Content', () => {

    test.beforeAll(async () => {
        await Environment.reset();
    });

    test('Changed page content can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('And I navigate to the changed page content', async () => {
            await backend.gotoModule('Page');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1b.1 Page content - changed');
        });

        await test.step('When I publish the record via "Publish Overview"', async () => {
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-65"]');
            await expect(recordRow).toBeVisible();

            const infoIcon = recordRow.locator('[data-action="opendirtypropertieslistcontainer"]');
            await infoIcon.click();

            const arrowRight = backend.contentFrame.locator('.icon-actions-arrow-right');
            await expect(arrowRight).toBeVisible();

            await expect(backend.contentFrame.locator('body')).toContainText('1b.1 Header - changed');

            await arrowRight.click();

            await expect(backend.contentFrame.locator('body')).toContainText('The selected record has been published successfully');
        });

        await test.step('Then the changes should be visible in the Foreign Backend', async () => {

            const foreignContext = await browser.newContext();
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Page');
            await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('1b.1 Page content - changed');

            await foreignPage.waitForTimeout(2000);

            // Edit the content element (uid 49 from PHP test)
            const editButton = foreignBackend.contentFrame.locator('div[data-table="tt_content"][data-uid="49"] a[title="Edit"]').first();
            await expect(editButton).toBeVisible();
            await editButton.click();

            const headerInput = foreignBackend.contentFrame.locator('[data-formengine-input-name="data[tt_content][49][header]"]');
            await expect(headerInput).toHaveValue(/1b.1 Header - changed/);

            await foreignContext.close();
        });
    });
});
