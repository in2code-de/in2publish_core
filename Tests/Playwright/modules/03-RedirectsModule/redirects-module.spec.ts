import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';

test.describe('Redirects Module', () => {

    /**
     * Tests that a redirect without site association can be published with site association.
     * Mirrors Tests/Browser/RedirectsModule/RedirectsModuleTest.php.
     * Redirect 19 exists only in the local fixture so that it starts unpublished.
     */
    test('Redirect without association can be published with site association', async ({ page, backend }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open the Publish Redirects module', async () => {
            await backend.gotoModule('Publish Redirects');

            await expect(backend.contentFrame.locator('body')).toContainText('t3://page?uid=67&_language=0');
            await expect(backend.contentFrame.locator('body')).toContainText('t3://page?uid=39&_language=0');
            await expect(backend.contentFrame.locator('body')).toContainText(
                '/extin2publish/8-treatremovedanddeletedasdifference'
            );
        });

        await test.step('And I click "Publish with site association"', async () => {
            const publishLink = backend.contentFrame.locator(
                'a[title="Publish with site association"][href*="redirect=19"]'
            );
            await expect(publishLink).toBeVisible();
            await publishLink.click();
        });

        await test.step('And I select site "main" and publish', async () => {
            // Select the site association
            await backend.contentFrame.locator('[name="properties[siteId]"]').selectOption('main');

            // Click save and publish
            await backend.contentFrame.locator('[name="_saveandpublish"]').click();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'Associated redirect Redirect [19] (local.v13.in2publish-core.de) /extin2publish/8-treatremovedanddeletedasdifference -> t3://page?uid=39&_language=0 with site main'
            );
        });
    });
});
