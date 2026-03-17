import { test, expect } from '@fixtures/setup-fixtures';
import config from '../../config';
import { Environment } from '@helpers/Environment';

test.describe('Backend User Preferences Reset', () => {

    test.beforeAll(async () => {
        await Environment.reset();
    });

    /**
     * Regression test: Backend user settings can be reset without errors.
     * Mirrors Tests/Browser/Regression/BackendUserPreferencesResetTest.php
     */
    test('Backend user settings can be reset', async ({ page, backend }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I open User Settings via the toolbar', async () => {
            // Click the user toolbar item
            await page.locator('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem').click();
            await page.locator('text=User Settings').click();
        });

        await test.step('And I click "Reset configuration"', async () => {
            const resetButton = backend.contentFrame.locator('button:has-text("Reset configuration")');
            await expect(resetButton).toBeVisible({ timeout: 10000 });
            await resetButton.click();

            // Wait for the confirmation button to appear
            const confirmButton = backend.contentFrame.locator('input[data-event-payload="resetConfiguration"]');
            await expect(confirmButton).toBeVisible();
            await confirmButton.click();
        });

        await test.step('And I confirm the modal dialog', async () => {
            // Handle the modal confirmation (in main document, not iframe)
            const okButton = page.locator('typo3-backend-modal button:has-text("OK"), .modal button:has-text("OK")');
            await expect(okButton).toBeVisible({ timeout: 5000 });
            await okButton.click();
        });

        await test.step('Then I should see the success message', async () => {
            await expect(backend.contentFrame.locator('body')).toContainText(
                'The user settings have been reset to default values and temporary data has been cleared.'
            );
        });
    });
});
