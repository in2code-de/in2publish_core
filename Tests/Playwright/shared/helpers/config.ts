import { Typo3TestConfig } from '../types';

const defaultBackendLogin = {
  username: 'admin',
  password: 'password',
};

export function createConfig(overrides: Partial<Typo3TestConfig> = {}): Typo3TestConfig {
  const baseUrl = overrides.baseUrl
    || process.env.PLAYWRIGHT_BASE_URL
    || process.env.BASE_URL
    || 'https://localhost/';

  const backendUrl = overrides.backendUrl
    || process.env.PLAYWRIGHT_BACKEND_URL
    || `${baseUrl.replace(/\/$/, '')}/typo3/`;

  const config: Typo3TestConfig = {
    baseUrl,
    backendUrl,
    login: {
      backend: overrides.login?.backend ?? {
        username: process.env.TYPO3_BACKEND_ADMIN_USERNAME || defaultBackendLogin.username,
        password: process.env.TYPO3_BACKEND_ADMIN_PASSWORD || defaultBackendLogin.password,
      },
      frontend: overrides.login?.frontend,
    },
    storageStatePath: overrides.storageStatePath,
  };

  return config;
}
