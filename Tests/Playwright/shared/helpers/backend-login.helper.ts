import { expect } from '../playwright';
import type { Page } from '../playwright';
import { Typo3TestConfig } from '../types';

type BackendLoginOptions = {
  url?: string;
  username?: string;
  password?: string;
};

export async function backendLogin(
  page: Page,
  config: Typo3TestConfig,
  options?: BackendLoginOptions,
): Promise<void> {
  const url = options?.url || config.backendUrl;
  const username = options?.username || config.login.backend?.username || 'admin';
  const password = options?.password || config.login.backend?.password || 'password';

  await page.goto(url);
  await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});

  const isLoggedIn = await page.locator('.scaffold-header')
    .isVisible({ timeout: 2000 })
    .catch(() => false);

  if (!isLoggedIn) {
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Login' }).click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.scaffold-header')).toBeVisible({ timeout: 15000 });
  }
}

export function createBackendLoginSetup(config: Typo3TestConfig, storageStatePath: string) {
  return async (page: Page): Promise<void> => {
    await backendLogin(page, config);
    await page.context().storageState({ path: storageStatePath });
  };
}
