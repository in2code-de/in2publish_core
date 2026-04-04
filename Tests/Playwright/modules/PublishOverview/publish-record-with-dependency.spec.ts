import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { restoreDatabases } from '../../helpers/direct-restore';

test.describe('Publish Record With Dependency', () => {

    // DB restore before each test (including retries) to ensure clean workflow states.
    test.beforeEach(async () => {
        await restoreDatabases();
    });

    /**
     * Tests that a record with unfulfilled dependency becomes publishable after dependencies are fulfilled.
     * Mirrors Tests/Browser/Dependency/PublishingRecordWithDependencyTest.php
     *
     * Uses 'publisher-page-tree-publish' user (not admin) to test permission-based publishing.
     */
    test('Record with unfulfilled dependency is publishable after dependencies are fulfilled', async ({ page, backend }) => {

        await test.step('Given I am logged in as publisher-page-tree-publish', async () => {
            // Clear cookies to log out, then log in as the publisher user
            await page.context().clearCookies();
            await page.goto(config.local.baseUrl);
            await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

            await page.getByLabel('Username').fill('publisher-page-tree-publish');
            await page.getByLabel('Password').fill('publisher-page-tree-publish');
            await page.getByRole('button', { name: 'Login' }).click();
            await page.waitForLoadState('networkidle');
            await expect(page.locator('.scaffold-header')).toBeVisible({ timeout: 15000 });
        });

        await test.step('And I navigate to the parent page in Publish Overview', async () => {
            await backend.gotoModule('Publish Overview');
            await backend.searchInPageTreeAndSelectFirstOccurrence('5c.1 Parent not published');

            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('5c.1 Parent not published', { timeout: 10000 });
            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('5c.1.1 Child Ready to Publish');
        });

        await test.step('Then the parent page (pages-35) should be publishable', async () => {
            await expect(
                backend.contentFrame.locator('[data-record-identifier="pages-35"] .icon-actions-arrow-right')
            ).toBeVisible();
        });

        await test.step('And the child page (pages-36) should show a dependency warning (not publishable)', async () => {
            await expect(
                backend.contentFrame.locator('[data-record-identifier="pages-36"] .icon-actions-exclamation-triangle-alt')
            ).toBeVisible();

            // Expand dirty properties to see dependency messages
            await backend.contentFrame.locator(
                '[data-record-identifier="pages-36"] [data-action="opendirtypropertieslistcontainer"]'
            ).click();

            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-36"]');
            await expect(recordRow).toContainText(
                '"5c.1 Parent not published" must be published first'
            );
            await expect(recordRow).toContainText(
                '"5c.1.1 Child Ready to Publish" is published first'
            );
            await expect(recordRow).toContainText(
                'The record "Header on not published Parent page 5c.1" is a target of the shortcut record'
            );
        });

        await test.step('When I publish the parent page (pages-35)', async () => {
            await backend.contentFrame.locator(
                '[data-record-identifier="pages-35"] .icon-actions-arrow-right'
            ).click();

            // Wait for the publish confirmation before re-opening the module
            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully', { timeout: 30000 }
            );
        });

        await test.step('Then after re-opening Publish Overview, the child should now be publishable', async () => {
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('5c.1 Parent not published', { timeout: 10000 });
            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('5c.1.1 Child Ready to Publish');

            // Exclamation triangle should be gone
            await expect(
                backend.contentFrame.locator('[data-record-identifier="pages-36"] .icon-actions-exclamation-triangle-alt')
            ).not.toBeVisible();

            // Publish arrow should now be available
            await expect(
                backend.contentFrame.locator('[data-record-identifier="pages-36"] .icon-actions-arrow-right')
            ).toBeVisible();
        });
    });
});
