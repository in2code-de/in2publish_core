import { test, expect } from '@fixtures/setup-fixtures';
import { BackendPage } from '@fixtures/backend-page';
import config from '../../config';
import { restoreDatabases } from '@helpers/DbRestore';

test.describe('UID Clash', () => {

    // Each use case requires a fresh DB state (mirrors PHP AbstractBrowserTestCase::setUp)
    // Uses direct mysql2 connection — works inside the Playwright Docker container
    test.beforeEach(async () => {
        await restoreDatabases();
    });

    /**
     * Helper: Publish page 76 via Publish Overview.
     */
    async function publishPage76(backend: BackendPage) {
        await backend.gotoModule('Page');
        await backend.searchInPageTreeAndSelectFirstOccurrence('24 Page with Category 1 and Category 2');
        await backend.gotoModule('Publish Overview');

        await expect(
            backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
        ).toBeVisible({ timeout: 10000 });
        await expect(
            backend.contentFrame.locator('[data-record-identifier="pages-76"]')
        ).toBeVisible();

        await backend.contentFrame.locator('.icon-actions-arrow-right').click();

        await expect(backend.contentFrame.locator('body')).toContainText(
            'The selected record has been published successfully', { timeout: 30000 }
        );
    }

    /**
     * Helper: Publish news on page "News Folder" via Publish Overview.
     */
    async function publishNews76(backend: BackendPage) {
        await backend.gotoModule('List');
        await backend.searchInPageTreeAndSelectFirstOccurrence('News Folder');
        await backend.gotoModule('Publish Overview');

        await expect(
            backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
        ).toBeVisible({ timeout: 10000 });

        await backend.contentFrame.locator('.icon-actions-arrow-right').click();

        await expect(backend.contentFrame.locator('body')).toContainText(
            'The selected record has been published successfully', { timeout: 30000 }
        );
    }

    /**
     * Helper: Assert page 76 exists in Foreign.
     */
    async function assertPage76Published(foreignBackend: BackendPage) {
        await foreignBackend.gotoModule('Page');
        await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('24 Page with Category 1 and Category 2');
    }

    /**
     * Helper: Assert news 76 exists in Foreign.
     */
    async function assertNews76Published(foreignBackend: BackendPage) {
        await foreignBackend.gotoModule('List');
        await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('News Folder');

        await expect(
            foreignBackend.contentFrame.locator('body')
        ).toContainText('24 news with Category 1', { timeout: 10000 });
    }

    /**
     * Helper: Assert both categories exist in Foreign.
     * Checks the sys_category section in the List module (not the page titles which also mention categories).
     */
    async function assertBothCategoriesPublished(foreignBackend: BackendPage) {
        await foreignBackend.gotoModule('List');
        await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('Home');

        // The Category section header shows the count: "Category (2)" for 2 records
        await expect(
            foreignBackend.contentFrame.locator('body')
        ).toContainText('Category (2)', { timeout: 15000 });
    }

    /**
     * Helper: Assert only Category 1 is published (not Category 2).
     * Checks the sys_category section in the List module (not the page titles which also mention categories).
     */
    async function assertOnlyCategory1Published(foreignBackend: BackendPage) {
        await foreignBackend.gotoModule('List');
        await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('Home');

        // The Category section header shows the count: "Category (1)" for only 1 record
        await expect(
            foreignBackend.contentFrame.locator('body')
        ).toContainText('Category (1)', { timeout: 15000 });
    }

    /**
     * Use Case 1: Page 24 is published in Overview Module.
     * Result: Page is published, both categories are published.
     */
    test('Use Case 1: Publishing page publishes both categories', async ({ backend, browser }) => {
        test.setTimeout(120_000);

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I publish page 76', async () => {
            await publishPage76(backend);
        });

        await test.step('Then page and both categories are visible on Foreign', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await assertPage76Published(foreignBackend);
                await assertBothCategoriesPublished(foreignBackend);
            });
        });
    });

    /**
     * Use Case 2: News folder with News 24 is published in Overview Module.
     * Result: News 24 is published, Page 24 is not published, only Category 1 is published.
     */
    test('Use Case 2: Publishing news publishes only Category 1', async ({ backend, browser }) => {
        test.setTimeout(120_000);

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I publish news 76', async () => {
            await publishNews76(backend);
        });

        await test.step('Then news and only Category 1 are visible on Foreign', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await assertNews76Published(foreignBackend);
                await assertOnlyCategory1Published(foreignBackend);
            });
        });
    });

    /**
     * Use Case 3: Page 24 is published first, then News 24.
     * Result: First only page, categories and page mm-records, then news and news mm-record.
     */
    test('Use Case 3: Publishing page then news publishes both categories', async ({ backend, browser }) => {
        test.setTimeout(180_000);

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I publish page 76 first, then news 76', async () => {
            await publishPage76(backend);
            await publishNews76(backend);
        });

        await test.step('Then page, news and both categories are visible on Foreign', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await assertPage76Published(foreignBackend);
                await assertNews76Published(foreignBackend);
                await assertBothCategoriesPublished(foreignBackend);
            });
        });
    });
});