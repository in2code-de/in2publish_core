import { test as setup, expect } from '@playwright/test';
import { createGlobalLoginSetup } from '@in2code/typo3-playwright/setup';
import config from './config';
import { fullRestore } from './helpers/direct-restore';

const performLogin = createGlobalLoginSetup(config, 'Tests/Playwright/.auth/login.json');

setup('reset environment and authenticate', async ({ page }) => {
  await fullRestore();
  await performLogin(page);

  // Warm up TYPO3 caches by navigating to the Page module and waiting for the
  // page tree to fully load. After fullRestore() clears DB caches, the first
  // backend requests are slow due to cache rebuilding. Without this warmup,
  // subsequent tests timeout waiting for the page tree search to return results.
  // TYPO3 v14: module menu uses menuitem elements; "Page" module is now "Layout"
  const moduleLink = page.locator('nav[aria-label="Module Menu"] [role="menuitem"]:has-text("Layout")');
  await moduleLink.click();
  await expect(page.locator('iframe#typo3-contentIframe')).toBeVisible({ timeout: 15000 });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  // Wait for page tree to be fully rendered
  await expect(page.locator('[role="treeitem"]').first()).toBeVisible({ timeout: 30000 });
  await page.waitForTimeout(2000);
});
