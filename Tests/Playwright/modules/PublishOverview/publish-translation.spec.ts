import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { restoreDatabases } from '../../helpers/direct-restore';

test.describe('Publish Translation', () => {

    test.beforeEach(async () => {
        await restoreDatabases();
    });

    /**
     * Test Case 1d.1: Translated content in free mode can be published.
     * Mirrors Tests/Browser/PublishTranslationTest.php::testTranslatedContentInFreeModeCanBePublished
     */
    test('Translated content in free mode can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open "Publish Overview" and select the free mode translation page', async () => {
            await backend.gotoModule('Publish Overview');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1d.1 Free Mode');

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

            // Verify local and foreign values (scoped to the record to avoid strict mode violation)
            await expect(
                recordRow.locator('.in2publish-dirty-properties-local')
            ).toContainText('Header in German - changed');
            await expect(
                recordRow.locator('.in2publish-dirty-properties-foreign')
            ).toContainText('Header in German');
        });

        await test.step('And I publish the record', async () => {
            const arrowRight = backend.contentFrame.locator(
                '[data-record-identifier="pages-72"] .icon-actions-arrow-right'
            );
            await expect(arrowRight).toBeVisible();
            await arrowRight.click();

            await backend.waitUntilPublishingFinished();
            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully',
                { timeout: 30000 }
            );
        });

        await test.step('Then the record should show as unchanged after re-opening Publish Overview', async () => {
            await backend.gotoModule('Publish Overview');

            const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-72"]');
            await expect(recordRow).toBeVisible();
            await expect(recordRow.locator('.in2publish-badge--unchanged')).toBeVisible();
        });

        await test.step('And the translated content should be visible in the Foreign Backend', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);

            // Navigate the content iframe to the edit form for the German tt_content (uid 54)
            await foreignPage.evaluate(() => {
                const iframe = document.getElementById('typo3-contentIframe') as HTMLIFrameElement;
                iframe.src = '/typo3/record/edit?edit[tt_content][54]=edit';
            });
            await foreignPage.waitForTimeout(3000);

            await expect(
                foreignBackend.contentFrame.locator('body')
            ).toContainText('Header in German - changed', { timeout: 10000 });

            await foreignContext.close();
        });
    });

    /**
     * Test Case 1d.2: Translated content in connected mode can be published.
     * Mirrors Tests/Browser/PublishTranslationTest.php::testTranslatedContentInConnectedModeCanBePublished
     */
    test('Translated content in connected mode can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open "Publish Overview" and select the connected mode page', async () => {
            await backend.gotoModule('Publish Overview');
            await backend.searchInPageTreeAndSelectFirstOccurrence('1d.2 Connected Mode');

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

            await expect(
                recordRow.locator('.in2publish-dirty-properties-local')
            ).toContainText('Header in German - changed');
            await expect(
                recordRow.locator('.in2publish-dirty-properties-foreign')
            ).toContainText('Header in German');
        });

        await test.step('And I publish the record', async () => {
            const arrowRight = backend.contentFrame.locator(
                '[data-record-identifier="pages-75"] .icon-actions-arrow-right'
            );
            await expect(arrowRight).toBeVisible();
            await arrowRight.click();

            await backend.waitUntilPublishingFinished();
            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully',
                { timeout: 30000 }
            );
        });

        await test.step('Then the record should show as unchanged', async () => {
            await backend.gotoModule('Publish Overview');

            await expect(
                backend.contentFrame.locator('[data-record-identifier="pages-75"] .in2publish-badge--unchanged')
            ).toBeVisible();
        });

        await test.step('And the translated content should be visible in the Foreign Backend', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);

            // Navigate the content iframe to the edit form for the German tt_content (uid 56)
            await foreignPage.evaluate(() => {
                const iframe = document.getElementById('typo3-contentIframe') as HTMLIFrameElement;
                iframe.src = '/typo3/record/edit?edit[tt_content][56]=edit';
            });
            await foreignPage.waitForTimeout(3000);

            await expect(
                foreignBackend.contentFrame.locator('body')
            ).toContainText('Header in German - changed', { timeout: 10000 });

            await foreignContext.close();
        });
    });
});
