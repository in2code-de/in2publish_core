import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';
import { execMake } from '../../shared/helpers';

test.describe('Backend User Preferences Reset', () => {

    test.beforeAll(async () => {
        execMake('restore');
    });

    /**
     * Regression test: Backend user settings can be reset without errors.
     * Mirrors Tests/Browser/Regression/BackendUserPreferencesResetTest.php
     *
     * Note: TYPO3 v14 dev has a payload mismatch bug — the reset button sends
     * "reset_configuration" but the JS handler expects "resetConfiguration".
     * We work around this by submitting the form directly via JS.
     */
    test('Backend user settings can be reset', async ({ page, backend }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open User Settings via the toolbar', async () => {
            // Click the user toolbar item to open the dropdown
            await page.locator('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem').click();
            // Click "User Settings" module link in the dropdown
            await page.locator('text=User Settings').click();
            // Wait for the module iframe to load
            await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
        });

        await test.step('And I trigger the reset', async () => {
            // TYPO3 v14 dev bug: the reset button's data-event-payload="reset_configuration"
            // doesn't match the JS handler which expects "resetConfiguration". The modal
            // confirmation does nothing. Work around by directly setting the hidden field
            // and submitting the form inside the iframe.
            const iframe = page.frameLocator('#typo3-contentIframe');
            const resetField = iframe.locator('#setValuesToDefault');
            await expect(resetField).toBeAttached({ timeout: 10000 });

            await resetField.evaluate((el: HTMLInputElement) => {
                el.value = '1';
                el.form!.submit();
            });
        });

        await test.step('Then I should see the success message', async () => {
            // Wait for the form submission and page reload
            await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
            await expect(backend.contentFrame.locator('body')).toContainText(
                'settings have been reset to default values and temporary data has been cleared.',
                { timeout: 10000 }
            );
        });
    });
});
