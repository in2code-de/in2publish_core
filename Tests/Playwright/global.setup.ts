import { test as setup, expect } from '@playwright/test';
import { createGlobalLoginSetup } from './shared/setup/index';
import { execMake } from './shared/helpers';
import config from './config';

const performLogin = createGlobalLoginSetup(config, 'Tests/Playwright/.auth/login.json');

setup('reset environment and authenticate', async ({ page }) => {

  // Reset the local + foreign database and fileadmin.
  execMake('restore');

  await performLogin(page);
});
