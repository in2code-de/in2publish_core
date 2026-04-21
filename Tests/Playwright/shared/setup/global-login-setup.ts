import type { Page } from '../playwright';
import { backendLogin } from '../helpers/backend-login.helper';
import { Typo3TestConfig } from '../types';

export function createGlobalLoginSetup(config: Typo3TestConfig, storageStatePath: string) {
  return async (page: Page): Promise<void> => {
    console.log('Authenticating as admin...');
    await backendLogin(page, config);
    await page.context().storageState({ path: storageStatePath });
    console.log('Authentication state saved to', storageStatePath);
  };
}
