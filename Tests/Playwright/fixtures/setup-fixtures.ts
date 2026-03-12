import { test as base } from '@playwright/test';
import { BackendPage } from './backend-page';

type Fixtures = {
  backend: BackendPage;
};

/**
 * Extended Playwright test with TYPO3 backend fixture
 *
 * Usage:
 * ```typescript
 * import { test, expect } from './fixtures/setup-fixtures';
 *
 * test('my test', async ({ backend }) => {
 *   await backend.gotoModule('Publisher Tools');
 *   await expect(backend.contentFrame.locator('h1')).toBeVisible();
 * });
 * ```
 */
export const test = base.extend<Fixtures>({
  backend: async ({ page }, use) => {
    const backend = new BackendPage(page);
    await use(backend);
  },
});

export { expect } from '@playwright/test';
