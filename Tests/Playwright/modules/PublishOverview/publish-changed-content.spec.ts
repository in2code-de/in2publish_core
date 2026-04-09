import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { fullRestore } from '../../helpers/direct-restore';

test.describe('Publish Changed Content', () => {

    test.beforeAll(async () => {
        await fullRestore();
    });

    test('Changed page content can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open "Publish Overview" and select the changed page', async () => {
            await backend.gotoModule('Publish Overview');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1b.1 Page content - changed');

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
            await foreignBackend.gotoModule('Page');
            await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('1b.1 Page content - changed');

            await foreignPage.waitForTimeout(2000);

            const editButton = foreignBackend.contentFrame.locator('div[data-table="tt_content"][data-uid="49"] typo3-backend-contextual-record-edit-trigger[title="Edit"]').first();
            await expect(editButton).toBeVisible();
            await editButton.click();

            await expect(foreignBackend.contentFrame.locator('text="1b.1 Header - changed"')).toBeVisible();

            await foreignContext.close();
        });
    });
});
