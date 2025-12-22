import { test as setup, expect } from '@playwright/test';
import config from './config';
import { Environment } from './helpers/Environment';

setup('reset environment and authenticate', async ({ page }) => {
  // Reset environment BEFORE authenticating so the session isn't invalidated
  await Environment.reset();

  console.log('Authenticating as admin...');

  await page.goto(config.local.baseUrl);

  // Wait for login page to load
  await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});

  // Check if already logged in
  const isLoggedIn = await page.locator('.scaffold-header')
    .isVisible({ timeout: 2000 })
    .catch(() => false);

  if (!isLoggedIn) {
    // Fill in login form
    await page.getByLabel('Username').fill(config.login.admin.username);
    await page.getByLabel('Password').fill(config.login.admin.password);
    await page.getByRole('button', { name: 'Login' }).click();

    // Wait for backend to load
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.scaffold-header')).toBeVisible({ timeout: 15000 });

    console.log('Login successful');
  } else {
    console.log('Already logged in');
  }

  // Save authentication state
  await page.context().storageState({ path: 'Tests/Playwright/.auth/login.json' });
  console.log('Authentication state saved');
});
