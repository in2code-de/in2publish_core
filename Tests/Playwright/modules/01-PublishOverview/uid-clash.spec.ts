import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { execMake } from '../../shared/helpers';

test.describe('UID Clash Test: verify publishing of relations to records with same uid but different table', () => {

     test.setTimeout(120000);
     // DB restore is required here between each test - published categories need to be reset
     test.beforeEach(async () => {
         execMake('restore-db');
     });

    async function publishPage76(backend: BackendPage) {
        await backend.gotoModule('Publish Overview');
        await backend.searchInPageTreeAndSelectFirstOccurrence('24 Page with Category 1 and Category 2');

        await expect(
            backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
        ).toBeVisible({ timeout: 15000 });

        await expect(
            backend.contentFrame.locator('[data-record-identifier="pages-76"]')
        ).toBeVisible({ timeout: 15000 });

        await backend.contentFrame.locator('.icon-actions-arrow-right').click();

        await backend.waitUntilPublishingFinished();
        await expect(backend.contentFrame.locator('body')).toContainText(
            'The selected record has been published successfully',
            { timeout: 30000 }
        );
    }

    async function publishNews76(backend: BackendPage) {
        await backend.gotoModule('Publish Overview');
        await backend.searchInPageTreeAndSelectFirstOccurrence('News Folder Core');

        await expect(
            backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
        ).toBeVisible({ timeout: 15000 });

        await backend.contentFrame.locator('.icon-actions-arrow-right').click();

        await backend.waitUntilPublishingFinished();
        await expect(backend.contentFrame.locator('body')).toContainText(
            'The selected record has been published successfully',
            { timeout: 30000 }
        );
    }

    async function assertPage76Published(foreignBackend: BackendPage) {
        // The published page lives at Home > EXT:in2publish_core > 24 Page...,
        // so it does not appear in the List module at Home (only direct children do).
        // Use the page tree search to confirm the node was published to Foreign.
        await foreignBackend.gotoModule('Page');
        await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('24 Page with Category 1 and Category 2');
        await expect(foreignBackend.contentFrame.locator('body')).toContainText('24 Page with Category 1 and Category 2', { timeout: 15000 });
    }

    async function assertNews76Published(foreignBackend: BackendPage) {
        await foreignBackend.gotoModule('Page');
        await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('News Folder Core');
        await foreignBackend.gotoModule('List');
        await expect(foreignBackend.contentFrame.locator('body')).toContainText('24 news with Category 1', { timeout: 15000 });
    }

    async function assertOnlyCategory1Published(foreignBackend: BackendPage) {
        await foreignBackend.gotoModule('Page');
        await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('Home');
        await foreignBackend.gotoModule('List');
        await expect(foreignBackend.contentFrame.locator('body')).toContainText('"Category 1"', { timeout: 15000 });
        await expect(foreignBackend.contentFrame.locator('body')).not.toContainText('"Category 2"', { timeout: 5000 });
    }

    async function assertBothCategoriesPublished(foreignBackend: BackendPage) {
        await foreignBackend.gotoModule('Page');
        await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('Home');
        await foreignBackend.gotoModule('List');
        await expect(foreignBackend.contentFrame.locator('body')).toContainText('"Category 1"', { timeout: 15000 });
        await expect(foreignBackend.contentFrame.locator('body')).toContainText('"Category 2"', { timeout: 5000 });
    }
    /**
     * Use Case 1: Page 24 is published in Overview Module.
     * Result: Page is published, both categories are published.
     */
    test('Use Case 1: Publishing page publishes both categories', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I publish page 76', async () => {
            await publishPage76(backend);
        });

        await test.step('Then page and both categories are visible on Foreign', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);

            await assertPage76Published(foreignBackend);
            await assertBothCategoriesPublished(foreignBackend);

            await foreignContext.close();
        });
    });

    /**
     * Use Case 2: News folder with News 24 is published in Overview Module.
     * Result: News 24 is published, Page 24 is not published, only Category 1 is published.
     */
    test('Use Case 2: Publishing news publishes only Category 1', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I publish news 76', async () => {
            await publishNews76(backend);
        });

        await test.step('Then news and only Category 1 are visible on Foreign', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);

            await assertNews76Published(foreignBackend);
            await assertOnlyCategory1Published(foreignBackend);

            await foreignContext.close();
        });
    });

    /**
     * Use Case 3: Page 24 is published first, then News 24.
     * Result: First only page, categories and page mm-records, then news and news mm-record.
     */
    test('Use Case 3: Publishing page then news publishes both categories', async ({ page, backend, browser }) => {
        test.setTimeout(120000);

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I publish page 76 first, then news 76', async () => {
            await publishPage76(backend);
            await publishNews76(backend);
        });

        await test.step('Then page, news and both categories are visible on Foreign', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);

            await assertPage76Published(foreignBackend);
            await assertNews76Published(foreignBackend);
            await assertBothCategoriesPublished(foreignBackend);

            await foreignContext.close();
        });
    });
});
