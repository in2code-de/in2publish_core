import { createConfig } from './shared/helpers/index';

/**
 * in2publish_core test configuration.
 * Extends the shared config with foreign instance URL.
 */
const baseConfig = createConfig({
  backendUrl: process.env.PLAYWRIGHT_BASE_URL || 'https://local.v14.in2publish.de/typo3/',
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
    baseUrl: process.env.PLAYWRIGHT_FOREIGN_BASE_URL || 'https://foreign.v14.in2publish.de/typo3/',
  },
};
