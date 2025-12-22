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
    // Find module link by exact text match
    const moduleLink = this.page.locator(`#modulemenu a.modulemenu-action[title="${moduleName}"]`);

    // Click and wait for module to load
    await moduleLink.click();

    // Wait for iframe to be visible
    await expect(this.page.locator('iframe#typo3-contentIframe')).toBeVisible({ timeout: 15000 });

    // Wait for module to be loaded (indicated by active class)
    await expect(moduleLink).toHaveClass(/modulemenu-action-active/, { timeout: 10000 });

    // Wait for network idle, e.g. there are no more requests for at least 500 ms
    await this.page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  }

  /**
   * Search for a page in the page tree and select the first occurrence
   * @param searchText The text to search for
   */
  async searchInPageTreeAndSelectFirstOccurrence(searchText: string): Promise<void> {
    // Wait for page tree to be loaded
    const pageTree = this.page.locator('.scaffold-content-navigation-component');
    await expect(pageTree).toBeVisible({ timeout: 10000 });

    // Find and fill the search input in the page tree (by placeholder text)
    const searchInput = this.page.locator('input[placeholder="Enter search term"]');
    await expect(searchInput).toBeVisible({ timeout: 5000 });
    await searchInput.fill(searchText);

    // Wait for search to process and results to appear
    await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});

    // Find the page tree node containing the search text
    // Click on the node container (not the text) to avoid interception issues
    const firstResult = this.page.locator('.node-contentlabel').filter({ hasText: searchText }).first();

    await expect(firstResult).toBeVisible({ timeout: 10000 });
    await firstResult.click();

    // Wait for the page to be selected and content to load
    await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
  }
}
