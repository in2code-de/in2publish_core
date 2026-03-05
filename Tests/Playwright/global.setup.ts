import { test as setup } from '@playwright/test';
import { createGlobalLoginSetup } from '@in2code/typo3-playwright/setup';
import config from './config';
import { Environment } from './helpers/Environment';

const performLogin = createGlobalLoginSetup(config, 'Tests/Playwright/.auth/login.json');

setup('reset environment and authenticate', async ({ page }) => {
  await Environment.reset();
  await performLogin(page);
});
