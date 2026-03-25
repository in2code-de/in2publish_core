import { test, expect } from '@fixtures/setup-fixtures';
import config from '../../config';
import { Environment } from '@helpers/Environment';
import { restoreDatabases } from '@helpers/DbRestore';

test.describe('Publish Translation', () => {

    test.beforeAll(async () => {
        await restoreDatabases();
        await Environment.reset();
    });

    /**
     * Test Case 1d.1: Translated content in free mode can be published.
     * Mirrors Tests/Browser/PublishTranslationTest.php::testTranslatedContentInFreeModeCanBePublished
     */
    test.skip('Translated content in free mode can be published', async ({ backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('And I navigate to the free mode translation page', async () => {
            await backend.gotoModule('Page');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1d.1 Free Mode');
        });

        await test.step('When I open "Publish Overview" and inspect the changed record', async () => {
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-72"]');
            await expect(recordRow).toBeVisible();

            // Verify changed badge
            await expect(recordRow.locator('.in2publish-badge--changed')).toBeVisible();

            // Expand dirty properties
            const infoIcon = recordRow.locator('[data-action="opendirtypropertieslistcontainer"]');
            await infoIcon.click();

            // Verify local and foreign values
            await expect(
                backend.contentFrame.locator('.in2publish-dirty-properties-local')
            ).toContainText('Header in German - Version 3');
            await expect(
                backend.contentFrame.locator('.in2publish-dirty-properties-foreign')
            ).toContainText('Header in German - Version 2');
        });

        await test.step('And I publish the record', async () => {
            const arrowRight = backend.contentFrame.locator(
                '[data-record-identifier="pages-72"] .icon-actions-arrow-right'
            );
            await expect(arrowRight).toBeVisible();
            await arrowRight.click();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully'
            );
        });

        await test.step('Then the record should show as unchanged after re-opening Publish Overview', async () => {
            await backend.gotoModule('Publish Overview');

            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-72"]');
            await expect(recordRow).toBeVisible();
            await expect(recordRow.locator('.in2publish-badge--unchanged')).toBeVisible();
        });

        await test.step('And the translated content should be visible in the Foreign Backend', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('List');
                await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('1d.1 Free Mode');

                await expect(
                    foreignBackend.contentFrame.locator('body')
                ).toContainText('Header in German - Version 3', { timeout: 10000 });
            });
        });
    });

    /**
     * Test Case 1d.2: Translated content in connected mode can be published.
     * Mirrors Tests/Browser/PublishTranslationTest.php::testTranslatedContentInConnectedModeCanBePublished
     */
    test('Translated content in connected mode can be published', async ({ backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('And I navigate to the connected mode translation page', async () => {
            await backend.gotoModule('Page');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1d.2 Connected Mode');
        });

        await test.step('When I open "Publish Overview" and inspect the changed record', async () => {
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
            ).toBeVisible({ timeout: 10000 });

            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-75"]');
            await expect(recordRow).toBeVisible();

            // Verify changed badge
            await expect(recordRow.locator('.in2publish-badge--changed')).toBeVisible();

            // Expand dirty properties
            const infoIcon = recordRow.locator('[data-action="opendirtypropertieslistcontainer"]');
            await infoIcon.click();

            const recordContent = recordRow.locator('.in2publish-page__content');
            await expect(
                recordContent.locator('.in2publish-dirty-properties-local')
            ).toContainText('Header in German - Version 3');
            await expect(
                recordContent.locator('.in2publish-dirty-properties-foreign')
            ).toContainText('Header in German - Version 2');
        });

        await test.step('And I publish the record', async () => {
            const arrowRight = backend.contentFrame.locator(
                '[data-record-identifier="pages-75"] .icon-actions-arrow-right'
            );
            await expect(arrowRight).toBeVisible();
            await arrowRight.click();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully'
            );
        });

        await test.step('Then the record should show as unchanged', async () => {
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('[data-record-identifier="pages-75"] .in2publish-badge--unchanged')
            ).toBeVisible();
        });

        await test.step('And the translated content should be visible in the Foreign Backend', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('List');
                await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('1d.2 Connected Mode');

                await expect(
                    foreignBackend.contentFrame.locator('body')
                ).toContainText('Header in German - Version 3', { timeout: 10000 });
            });
        });
    });
});
