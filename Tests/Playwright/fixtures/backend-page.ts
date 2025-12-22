import { Page, FrameLocator, expect, Locator } from '@playwright/test';

export class BackendPage {
  readonly page: Page;
  readonly moduleNavigation: Locator;
  readonly contentFrame: FrameLocator;

  constructor(page: Page) {
    this.page = page;
    this.moduleNavigation = this.page.locator('#modulemenu');
    this.contentFrame = this.page.frameLocator('#typo3-contentIframe');
  }

  /**
   * Navigate to a TYPO3 backend module
   * @param moduleName The name of the module to navigate to (e.g., 'Publisher Tools')
   */
  async gotoModule(moduleName: string): Promise<void> {
    // Find module link by text
    const moduleLink = this.page.locator('#modulemenu .modulemenu-name', {
      hasText: moduleName
    }).locator('xpath=ancestor::a');

    // Click and wait for module to load
    await moduleLink.click();

    // Wait for iframe to be visible
    await expect(this.page.locator('iframe#typo3-contentIframe')).toBeVisible({ timeout: 15000 });

    // Wait for module to be loaded (indicated by active class)
    await expect(moduleLink).toHaveClass(/modulemenu-action-active/, { timeout: 10000 });

    // Wait for network idle
    await this.page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  }
}
