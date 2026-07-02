import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';
import { execMake } from '../../shared/helpers';

test.describe('Redirects Module', () => {

    test.beforeAll(async () => {
        execMake('restore');
    });

    /**
     * Tests that a redirect without site association can be published with site association.
     * Mirrors Tests/Browser/RedirectsModule/RedirectsModuleTest.php
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
            const row = backend.contentFrame.locator('tbody tr').filter({
                hasText: '/extin2publish/8-treatremovedanddeletedasdifference',
            });
            const publishLink = row.getByRole('link', { name: 'Publish with site association' });
            await expect(publishLink).toBeVisible();
            await publishLink.click();
        });

        await test.step('And I select site "main" and publish', async () => {
            // Select the site association
            const siteSelector = backend.contentFrame.locator('[name="properties[siteId]"]');
            await expect(siteSelector).toBeVisible();
            await siteSelector.selectOption('main');

            // Click save and publish
            await backend.contentFrame.locator('[name="_saveandpublish"]').click();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'Associated redirect Redirect [19] (local.v14.in2publish.de) /extin2publish/8-treatremovedanddeletedasdifference -> t3://page?uid=39&_language=0 with site main'
            );
        });
    });
});
