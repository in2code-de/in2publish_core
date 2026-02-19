import { createConfig } from '@in2code/typo3-playwright/helpers';

/**
 * in2publish_core test configuration.
 * Extends the shared config with foreign instance URL.
 */
const baseConfig = createConfig({
  backendUrl: process.env.PLAYWRIGHT_BASE_URL || 'https://local.v13.in2publish-core.de/typo3/',
  login: {
    backend: {
      username: process.env.TYPO3_BACKEND_ADMIN_USERNAME || 'admin',
      password: process.env.TYPO3_BACKEND_ADMIN_PASSWORD || 'password',
    },
  },
});

export default {
  ...baseConfig,
  local: {
    baseUrl: baseConfig.backendUrl,
  },
  foreign: {
    baseUrl: process.env.PLAYWRIGHT_FOREIGN_BASE_URL || 'https://foreign.v13.in2publish-core.de/typo3/',
  },
};
