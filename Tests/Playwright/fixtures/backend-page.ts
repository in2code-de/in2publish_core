import { Page } from '@playwright/test';
import { BackendPage as BaseBackendPage } from '../shared/fixtures/index';
import config from '../config';

/**
 * in2publish-specific BackendPage.
 * Extends the shared BackendPage with the project config.
 */
export class BackendPage extends BaseBackendPage {
  constructor(page: Page) {
    super(page, config);
  }

  /**
   * Login to TYPO3 backend.
   * @param baseUrl Optional base URL (defaults to local backend URL from config)
   */
  async login(baseUrl?: string): Promise<void> {
    await super.login(baseUrl || config.local.baseUrl);
  }
}
