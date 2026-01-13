import { Page, FrameLocator, expect, Locator } from '@playwright/test';
import config from '../config';

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
   * Login to TYPO3 backend if not already logged in
   * @param baseUrl Optional base URL to use for login (defaults to config.local.baseUrl)
   */
  async login(baseUrl: string = config.local.baseUrl): Promise<void> {
    await this.page.goto(baseUrl);

    // Verify if we're logged in
    const isLoggedIn = await this.page.locator('.scaffold-header')
      .isVisible({ timeout: 5000 })
      .catch(() => false);

    if (!isLoggedIn) {
      // If not logged in, login manually
      await this.page.getByLabel('Username').fill(config.login.admin.username);
      await this.page.getByLabel('Password').fill(config.login.admin.password);
      await this.page.getByRole('button', { name: 'Login' }).click();
      await this.page.waitForLoadState('networkidle');
      await expect(this.page.locator('.scaffold-header')).toBeVisible();
    }
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
    await this.page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => { });

    // Additional wait to ensure iframe content has fully loaded and updated
    // Module switches can take time to refresh the iframe content
    await this.page.waitForTimeout(1000);

    // Wait for another network idle to catch any secondary requests
    await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => { });
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
    await expect(searchInput).toBeVisible({ timeout: 10000 });

    // Clear any existing search text first
    await searchInput.clear();
    await searchInput.fill(searchText);

    // Press Enter to trigger search if needed
    await searchInput.press('Enter');

    // Wait a bit for the search to filter results
    await this.page.waitForTimeout(800);

    // Wait for search to process and results to appear
    await this.page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => { });

    // Find tree items that match the search text
    // Use the ARIA role to find actual tree items, then filter by the text content
    const treeItems = this.page.locator('[role="treeitem"]');
    const matchingTreeItems = treeItems.filter({ hasText: searchText });

    const count = await matchingTreeItems.count();
    if (count === 0) {
      throw new Error(`No page tree nodes found matching "${searchText}"`);
    }

    console.log(`Found ${count} matching tree items for "${searchText}"`);

    // Get the first matching tree item and ensure it's stable
    const firstTreeItem = matchingTreeItems.first();

    // Wait for the first result to be visible and stable
    await expect(firstTreeItem).toBeVisible({ timeout: 10000 });
    await firstTreeItem.waitFor({ state: 'visible', timeout: 5000 });

    // Ensure the element is in a stable state before clicking
    await this.page.waitForTimeout(500);

    // Click on the tree item's clickable area (the content label)
    // We target the .node-contentlabel div which is the actual clickable element
    const clickableElement = firstTreeItem.locator('.node-contentlabel').first();
    await expect(clickableElement).toBeVisible({ timeout: 5000 });

    // Scroll into view to ensure it's clickable
    await clickableElement.scrollIntoViewIfNeeded();
    await this.page.waitForTimeout(300);

    // Click on the element (use force: true since element may overlap with others in tree)
    await clickableElement.click({ force: true });

    // Wait for the page to be selected and content to load
    await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => { });

    // Additional wait to ensure the selection is registered
    await this.page.waitForTimeout(1500);

    // Verify that a tree item is now selected (has active/selected state)
    const selectedItem = this.page.locator('[role="treeitem"][aria-selected="true"]');
    const isSelected = await selectedItem.isVisible({ timeout: 2000 }).catch(() => false);

    if (!isSelected) {
      console.warn(`Warning: No tree item appears to be selected after clicking "${searchText}"`);
    }

    console.log(`Selected first tree item matching "${searchText}"`);
  }
}
