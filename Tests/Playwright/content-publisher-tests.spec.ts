import { test, expect } from './fixtures/setup-fixtures';
import config from './config';

test.describe('Content Publisher Tests', () => {
    test.beforeEach(async ({ page, backend }) => {
        // Handle login
        await backend.login();

        // Navigate to Publisher Tools module
        await backend.gotoModule('Publisher Tools');
    });

    test('Tests View should show no errors', async ({ page, backend }) => {

        await test.step('Given I am logged in to the TYPO3 Backend and in the "Publisher Tools" module', async () => {
            await expect(page.locator('.scaffold-header')).toBeVisible();
            await expect(backend.contentFrame.getByRole('heading', { name: 'TYPO3 Content Publisher' })).toBeVisible();
        });

        const contentFrame = backend.contentFrame;

        await test.step('When I navigate to the "Tests" tab and run the internal tests', async () => {

            const testsTab = contentFrame
                .locator('a, button')
                .filter({ hasText: /^\s*Tests\s*$/ });

            await expect(testsTab).toBeVisible({ timeout: 10000 });
            await testsTab.click();
            await expect(contentFrame.locator('.callout-success').first()).toBeVisible({ timeout: 30000 });
        });

        await test.step('Then I should see success messages indicating passed tests', async () => {
            const successCount = await contentFrame.locator('.callout-success').count();
            expect(successCount, 'Should have successful tests').toBeGreaterThan(10);
        });

        await test.step('And I should see no warning or error messages', async () => {
            await expect(contentFrame.locator('.callout-warning')).toHaveCount(0);
            await expect(contentFrame.locator('.callout-danger')).toHaveCount(0);
        });
    });
});
