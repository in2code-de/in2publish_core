import { test as setup} from '@playwright/test';
import { createGlobalLoginSetup } from './shared/setup/index';
import { resetEnvironment } from './shared/helpers';
import config from './config';

const performLogin = createGlobalLoginSetup(config, 'Tests/Playwright/.auth/login.json');

setup('reset environment and authenticate', async ({ page }) => {

  // Restore all mutable state and perform the expensive schema/cache work once per run.
  await resetEnvironment(page, 'playwright-prepare');

  await performLogin(page);
});
