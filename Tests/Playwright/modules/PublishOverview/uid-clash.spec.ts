import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { restoreDatabases } from '../../helpers/direct-restore';
import mysql from 'mysql2/promise';

test.describe('UID Clash', () => {

    // DB restore + two publish operations + foreign verification needs more than 60s
    test.describe.configure({ timeout: 120000 });

    // Each use case requires a fresh DB state (mirrors PHP AbstractBrowserTestCase::setUp).
    // Uses direct DB restore since Environment.reset() is skipped in CI.
    test.beforeEach(async () => {
        await restoreDatabases();
    });

    /**
     * Helper: Publish page 76 via Publish Overview.
     */
    async function publishPage76(backend: BackendPage) {
        await backend.gotoModule('Publish Overview');
        await backend.searchInPageTreeAndSelectFirstOccurrence('24 Page with Category 1 and Category 2');

        await expect(
            backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
        ).toBeVisible({ timeout: 10000 });
        await expect(
            backend.contentFrame.locator('[data-record-identifier="pages-76"]')
        ).toBeVisible();

        await backend.contentFrame.locator('.icon-actions-arrow-right').click();

        await backend.waitUntilPublishingFinished();
        await expect(backend.contentFrame.locator('body')).toContainText(
            'The selected record has been published successfully',
            { timeout: 30000 }
        );
    }

    /**
     * Helper: Publish news on page "News Folder" (pages-33) via Publish Overview.
     * Handles the ambiguity of two pages named "News Folder" (pages-26 and pages-33).
     */
    async function publishNews76(backend: BackendPage, page: import('@playwright/test').Page) {
        await backend.gotoModule('Publish Overview');
        await backend.searchInPageTreeAndSelectFirstOccurrence('News Folder');

        // Verify we selected pages-33 (the one with news records, not pages-26)
        const hasCorrectPage = await backend.contentFrame.locator('[data-record-identifier="pages-33"]').count();
        if (hasCorrectPage === 0) {
            // Try selecting the second "News Folder" node in the page tree
            const pageTree = page.locator('typo3-backend-content-navigation');
            const treeItems = pageTree.locator('[role="treeitem"]').filter({ hasText: 'News Folder' });
            const secondItem = treeItems.nth(1);
            if (await secondItem.count() > 0) {
                const label = secondItem.locator('.node-contentlabel').first();
                await label.click({ force: true });
                await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
                await page.waitForTimeout(1000);
            }
        }

        await expect(
            backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
        ).toBeVisible({ timeout: 10000 });

        await backend.contentFrame.locator('.icon-actions-arrow-right').click();

        await backend.waitUntilPublishingFinished();
        await expect(backend.contentFrame.locator('body')).toContainText(
            'The selected record has been published successfully',
            { timeout: 30000 }
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
     * Navigates directly to the List module for pages-33 (the News Folder with news records)
     * to avoid disambiguation issues with two pages named "News Folder" in the page tree.
     */
    async function assertNews76Published(foreignBackend: BackendPage, foreignPage: import('@playwright/test').Page) {
        // Navigate directly to List module for page 33 via URL to avoid
        // "News Folder" disambiguation (two pages with the same name exist).
        const foreignBaseUrl = config.foreign.baseUrl;
        await foreignPage.goto(`${foreignBaseUrl}module/web/list?id=33`);
        await foreignPage.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
        await foreignPage.waitForTimeout(2000);

        // The URL navigation loads the full backend scaffold with List module in the iframe.
        // Check both locations since TYPO3 may render differently based on routing.
        try {
            await expect(
                foreignBackend.contentFrame.locator('body')
            ).toContainText('24 news with Category 1', { timeout: 10000 });
        } catch {
            // Fallback: content might be in the main page body instead of iframe
            await expect(
                foreignPage.locator('body')
            ).toContainText('24 news with Category 1', { timeout: 5000 });
        }
    }

    /**
     * Helper: Query the foreign database for published categories.
     */
    async function getForeignCategories(): Promise<string[]> {
        const connection = await mysql.createConnection({
            host: 'mysql', port: 3306, user: 'root', password: 'root', database: 'foreign',
        });
        try {
            const [rows] = await connection.query('SELECT title FROM sys_category WHERE deleted = 0 ORDER BY uid') as any;
            return rows.map((r: any) => r.title);
        } finally {
            await connection.end();
        }
    }

    /**
     * Helper: Assert both categories exist in Foreign.
     * Uses direct DB check because the TYPO3 List module doesn't reliably show
     * cross-page sys_category records, and page titles can cause false positives.
     */
    async function assertBothCategoriesPublished() {
        const categories = await getForeignCategories();
        expect(categories).toContain('"Category 1"');
        expect(categories).toContain('"Category 2"');
    }

    /**
     * Helper: Assert only Category 1 is published (not Category 2).
     * Uses direct DB check to avoid false positives from page titles
     * like "24 Page with Category 1 and Category 2".
     */
    async function assertOnlyCategory1Published() {
        const categories = await getForeignCategories();
        expect(categories).toContain('"Category 1"');
        expect(categories).not.toContain('"Category 2"');
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
            await publishNews76(backend, page);
        });

        await test.step('Then news and only Category 1 are visible on Foreign', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);

            await assertNews76Published(foreignBackend, foreignPage);
            await assertOnlyCategory1Published(foreignBackend);

            await foreignContext.close();
        });
    });

    /**
     * Use Case 3: Page 24 is published first, then News 24.
     * Result: First only page, categories and page mm-records, then news and news mm-record.
     */
    test('Use Case 3: Publishing page then news publishes both categories', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I publish page 76 first, then news 76', async () => {
            await publishPage76(backend);
            // Wait for the first publish to fully complete before starting the second
            await page.waitForTimeout(2000);
            await publishNews76(backend, page);
        });

        await test.step('Then page, news and both categories are visible on Foreign', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.clearCaches();

            await assertPage76Published(foreignBackend);
            await assertNews76Published(foreignBackend, foreignPage);
            await assertBothCategoriesPublished(foreignBackend);

            await foreignContext.close();
        });
    });
});
