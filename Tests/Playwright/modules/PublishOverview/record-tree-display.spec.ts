import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';
import { fullRestore } from '../../helpers/direct-restore';

test.describe('Record Tree Display', () => {

    test.beforeAll(async () => {
        await fullRestore();
    });

    /**
     * Tests that the page tree depth selector controls visible levels.
     * Mirrors Tests/Browser/RecordTreeDisplayTest.php
     *
     * DB page names (naming inconsistency in DB dump, but hierarchy is correct):
     * - "4 PageTree depth" (pid=4)
     *   - "4.1 Subpage - Level 1" (pid=27)
     *     - "4.1.1 Subpage - Level 2" (pid=28)
     *       - "4.1.1.1 Subpage - Level 4" (pid=29) ← named Level 4 in DB
     *         - "4.1.1.1.1 Subpage - Level 5" (pid=30) ← named Level 5 in DB
     *           - "Subpage - Level 5" (pid=31) ← 5th level, ambiguous name
     */
    test('The level of records to show can be selected', async ({ page, backend }) => {

        await test.step('Given I am logged in and navigate to the depth test page', async () => {
            await backend.login(config.local.baseUrl);
            await backend.gotoModule('Publish Overview');
            await backend.searchInPageTreeAndSelectFirstOccurrence('4 PageTree depth');

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
            await expect(body).toContainText('4.1 Subpage - Level 1');
            await expect(body).not.toContainText('4.1.1 Subpage - Level 2');
            await expect(body).not.toContainText('4.1.1.1 Subpage - Level 4');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).not.toBeVisible();
        });

        await test.step('When I select "2 levels", levels 1-2 are shown', async () => {
            await backend.contentFrame.locator('#in2publish__publishfilter_level').selectOption({ label: '2 levels' });
            await backend.contentFrame.locator('body').waitFor({ state: 'attached' });

            const body = backend.contentFrame.locator('body');
            await expect(body).not.toContainText('Home');
            await expect(body).not.toContainText('EXT:in2publish_core');
            await expect(body).toContainText('4 PageTree depth');
            await expect(body).toContainText('4.1 Subpage - Level 1');
            await expect(body).toContainText('4.1.1 Subpage - Level 2');
            await expect(body).not.toContainText('4.1.1.1 Subpage - Level 4');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).not.toBeVisible();
        });

        await test.step('When I select "3 levels", levels 1-3 are shown', async () => {
            await backend.contentFrame.locator('#in2publish__publishfilter_level').selectOption({ label: '3 levels' });
            await backend.contentFrame.locator('body').waitFor({ state: 'attached' });

            const body = backend.contentFrame.locator('body');
            await expect(body).not.toContainText('EXT:in2publish_core');
            await expect(body).toContainText('4 PageTree depth');
            await expect(body).toContainText('4.1 Subpage - Level 1');
            await expect(body).toContainText('4.1.1 Subpage - Level 2');
            await expect(body).toContainText('4.1.1.1 Subpage - Level 4');
            await expect(body).not.toContainText('4.1.1.1.1 Subpage - Level 5');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).not.toBeVisible();
        });

        await test.step('When I select "4 levels", levels 1-4 are shown', async () => {
            await backend.contentFrame.locator('#in2publish__publishfilter_level').selectOption({ label: '4 levels' });
            await backend.contentFrame.locator('body').waitFor({ state: 'attached' });

            const body = backend.contentFrame.locator('body');
            await expect(body).not.toContainText('EXT:in2publish_core');
            await expect(body).toContainText('4 PageTree depth');
            await expect(body).toContainText('4.1 Subpage - Level 1');
            await expect(body).toContainText('4.1.1 Subpage - Level 2');
            await expect(body).toContainText('4.1.1.1 Subpage - Level 4');
            await expect(body).toContainText('4.1.1.1.1 Subpage - Level 5');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).not.toBeVisible();
        });

        await test.step('When I select "5 levels", all levels are shown', async () => {
            await backend.contentFrame.locator('#in2publish__publishfilter_level').selectOption({ label: '5 levels' });
            await backend.contentFrame.locator('body').waitFor({ state: 'attached' });

            const body = backend.contentFrame.locator('body');
            await expect(body).not.toContainText('EXT:in2publish_core');
            await expect(body).toContainText('4 PageTree depth');
            await expect(body).toContainText('4.1 Subpage - Level 1');
            await expect(body).toContainText('4.1.1 Subpage - Level 2');
            await expect(body).toContainText('4.1.1.1 Subpage - Level 4');
            await expect(body).toContainText('4.1.1.1.1 Subpage - Level 5');
            await expect(backend.contentFrame.locator('[data-record-identifier="pages-32"]')).toBeVisible();
        });
    });
});
