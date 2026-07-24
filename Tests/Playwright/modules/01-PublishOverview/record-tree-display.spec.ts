import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';

test.describe('Record Tree Display', () => {

    /**
     * Tests that the page tree depth selector controls visible levels.
     * Mirrors Tests/Browser/RecordTreeDisplayTest.php
     *
     * DB page hierarchy (uid, pid, title):
     * - "4 PageTree depth"  (uid=27, pid=4)   ← module root
     *   - "Subpage - Level 1" (uid=28, pid=27)
     *     - "Subpage - Level 2" (uid=29, pid=28)
     *       - "Subpage - Level 3" (uid=30, pid=29)
     *         - "Subpage - Level 4" (uid=31, pid=30)
     *           - "Subpage - Level 5" (uid=32, pid=31)
     */
    test('The level of records to show can be selected', async ({ page, backend }) => {

        await test.step('Given I am logged in and navigate to the depth test page', async () => {
            await backend.login(config.local.baseUrl);
            await backend.gotoModule('Page');
            await backend.searchInPageTreeAndSelectFirstOccurrence('4 PageTree depth');
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });
        });

        await test.step('When I select "1 level", only level 1 subpage is shown', async () => {
            await backend.contentFrame.locator('#in2publish__publishfilter_level').selectOption({ label: '1 level' });
            await backend.contentFrame.locator('body').waitFor({ state: 'attached' });

            const body = backend.contentFrame.locator('body');
            await expect(body).not.toContainText('Home');
            await expect(body).not.toContainText('EXT:in2publish_core');
            await expect(body).toContainText('4 PageTree depth');
            await expect(body).toContainText('Subpage - Level 1');
            await expect(body).not.toContainText('Subpage - Level 2');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).not.toBeVisible();
        });

        await test.step('When I select "2 levels", levels 1-2 are shown', async () => {
            await backend.contentFrame.locator('#in2publish__publishfilter_level').selectOption({ label: '2 levels' });
            await backend.contentFrame.locator('body').waitFor({ state: 'attached' });

            const body = backend.contentFrame.locator('body');
            await expect(body).not.toContainText('Home');
            await expect(body).not.toContainText('EXT:in2publish_core');
            await expect(body).toContainText('4 PageTree depth');
            await expect(body).toContainText('Subpage - Level 1');
            await expect(body).toContainText('Subpage - Level 2');
            await expect(body).not.toContainText('Subpage - Level 3');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).not.toBeVisible();
        });

        await test.step('When I select "3 levels", levels 1-3 are shown', async () => {
            await backend.contentFrame.locator('#in2publish__publishfilter_level').selectOption({ label: '3 levels' });
            await backend.contentFrame.locator('body').waitFor({ state: 'attached' });

            const body = backend.contentFrame.locator('body');
            await expect(body).not.toContainText('EXT:in2publish_core');
            await expect(body).toContainText('4 PageTree depth');
            await expect(body).toContainText('Subpage - Level 1');
            await expect(body).toContainText('Subpage - Level 2');
            await expect(body).toContainText('Subpage - Level 3');
            await expect(body).not.toContainText('Subpage - Level 4');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).not.toBeVisible();
        });

        await test.step('When I select "4 levels", levels 1-4 are shown', async () => {
            await backend.contentFrame.locator('#in2publish__publishfilter_level').selectOption({ label: '4 levels' });
            await backend.contentFrame.locator('body').waitFor({ state: 'attached' });

            const body = backend.contentFrame.locator('body');
            await expect(body).not.toContainText('EXT:in2publish_core');
            await expect(body).toContainText('4 PageTree depth');
            await expect(body).toContainText('Subpage - Level 1');
            await expect(body).toContainText('Subpage - Level 2');
            await expect(body).toContainText('Subpage - Level 3');
            await expect(body).toContainText('Subpage - Level 4');
            await expect(body).not.toContainText('Subpage - Level 5');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).not.toBeVisible();
        });

        await test.step('When I select "5 levels", all levels are shown', async () => {
            await backend.contentFrame.locator('#in2publish__publishfilter_level').selectOption({ label: '5 levels' });
            await backend.contentFrame.locator('body').waitFor({ state: 'attached' });

            const body = backend.contentFrame.locator('body');
            await expect(body).not.toContainText('EXT:in2publish_core');
            await expect(body).toContainText('4 PageTree depth');
            await expect(body).toContainText('Subpage - Level 1');
            await expect(body).toContainText('Subpage - Level 2');
            await expect(body).toContainText('Subpage - Level 3');
            await expect(body).toContainText('Subpage - Level 4');
            await expect(body).toContainText('Subpage - Level 5');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).toBeVisible();
        });
    });
});
