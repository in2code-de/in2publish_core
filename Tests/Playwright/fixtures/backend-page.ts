import { Page, expect } from '@playwright/test';
import { BackendPage as BaseBackendPage } from '@in2code/typo3-playwright/fixtures';
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

  /**
   * Navigate to a TYPO3 backend module by name.
   * Overrides base class to handle TYPO3 v14 module renames and use direct URL
   * navigation for reliability after cache flushes.
   */
  async gotoModule(moduleName: string): Promise<void> {
    const v14ModuleNames: Record<string, string> = {
      'Page': 'Layout',
      'List': 'Records',
    };
    const resolvedName = v14ModuleNames[moduleName] || moduleName;
    const moduleLink = this.page.locator(`#modulemenu a.modulemenu-action[title="${resolvedName}"]`);

    const href = await moduleLink.getAttribute('href', { timeout: 5000 });
    if (href) {
      const baseUrl = this.page.url().split('/typo3/')[0];
      const fullUrl = href.startsWith('/') ? `${baseUrl}${href}` : href;
      await this.page.goto(fullUrl, { waitUntil: 'load', timeout: 30000 });
    } else {
      await moduleLink.click({ timeout: 5000 });
    }

    await expect(moduleLink).toHaveClass(/modulemenu-action-active/, { timeout: 15000 });
    await this.page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await this.page.waitForTimeout(1000);
  }

  /**
   * Search for a page in the page tree with retry logic.
   * The TYPO3 page tree can take variable time to load, so we retry
   * the search up to 3 times with increasing waits between attempts.
   */
  async searchInPageTreeAndSelectFirstOccurrence(searchText: string): Promise<void> {
    const maxRetries = 3;
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      try {
        // TYPO3 v14: page tree uses different selectors than the base class
        await expect(
          this.page.locator('typo3-backend-content-navigation')
        ).toBeVisible({ timeout: 30000 });

        const searchInput = this.page.locator('input[placeholder="Search page tree"]');
        await expect(searchInput).toBeVisible({ timeout: 30000 });
        await searchInput.clear();
        await searchInput.fill(searchText);
        await searchInput.press('Enter');
        await this.page.waitForTimeout(800);
        await this.page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

        const treeItems = this.page.locator('[role="treeitem"]');
        const matchingTreeItems = treeItems.filter({ hasText: searchText });
        const count = await matchingTreeItems.count();
        if (count === 0) {
          throw new Error(`No page tree nodes found matching "${searchText}"`);
        }

        const firstTreeItem = matchingTreeItems.first();
        await expect(firstTreeItem).toBeVisible({ timeout: 10000 });
        await this.page.waitForTimeout(500);

        const clickableElement = firstTreeItem.locator('.node-contentlabel').first();
        await expect(clickableElement).toBeVisible({ timeout: 5000 });
        await clickableElement.scrollIntoViewIfNeeded();
        await this.page.waitForTimeout(300);
        await clickableElement.click({ force: true });

        await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
        await this.page.waitForTimeout(1500);
        return;
      } catch (error) {
        if (attempt === maxRetries) {
          throw error;
        }
        await this.page.waitForTimeout(1000 * attempt);
        await this.page.reload();
        await this.page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
        await this.page.waitForTimeout(1000);
      }
    }
  }

  /**
   * Navigate through the file storage tree by clicking each path segment.
   * Used for Filelist and Publish Files modules.
   * @param pathSegments Array of folder names to navigate through (e.g., ['fileadmin', 'Testcases', '2b_published_file'])
   */
  async selectInFileStorageTree(pathSegments: string[]): Promise<void> {
    const fileTree = this.page.locator('typo3-backend-content-navigation');
    await expect(fileTree).toBeVisible({ timeout: 10000 });

    for (const segment of pathSegments) {
      const treeNode = fileTree.locator(`[role="treeitem"]`).filter({ hasText: segment });
      const firstNode = treeNode.first();
      await expect(firstNode).toBeVisible({ timeout: 10000 });

      // Expand the node if it has children and is not expanded
      const chevron = firstNode.locator('.node-toggle');
      if (await chevron.count() > 0) {
        const isExpanded = await firstNode.getAttribute('aria-expanded');
        if (isExpanded !== 'true') {
          await chevron.click();
          await this.page.waitForTimeout(500);
        }
      }

      // Click the label to select the folder
      const label = firstNode.locator('.node-contentlabel').first();
      await expect(label).toBeVisible({ timeout: 5000 });
      await label.scrollIntoViewIfNeeded();
      await label.click({ force: true });
      await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
      await this.page.waitForTimeout(500);
    }

    // Final wait for content to settle
    await this.page.waitForTimeout(1000);
  }

  /**
   * Wait until the in2publish loading overlay disappears after publishing.
   * Replaces the PHP ContentPublisherHelper::waitUntilPublishingFinished().
   */
  async waitUntilPublishingFinished(): Promise<void> {
    await expect(
      this.contentFrame.locator('.in2publish-loading-overlay')
    ).not.toBeVisible({ timeout: 30000 });
  }

  /**
   * Clear all TYPO3 caches via the backend toolbar button.
   */
  async clearCaches(): Promise<void> {
    const clearCacheBtn = this.page.locator('button').filter({ hasText: 'Clear cache' }).first();
    await clearCacheBtn.click();
    // v14 uses a menu with button items instead of .dropdown-menu.show with text links
    const flushAll = this.page.locator('button:has-text("Flush all caches")').first();
    await flushAll.click();
    await this.page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await this.page.waitForTimeout(2000);
  }

  /**
   * Click a TYPO3 modal button by its text (handles modals in the main document).
   * @param buttonText The text of the button to click (e.g., 'Publish', 'OK')
   */
  async clickModalButton(buttonText: string): Promise<void> {
    const modal = this.page.locator('typo3-backend-modal .modal, .modal.show').last();
    await expect(modal).toBeVisible({ timeout: 10000 });
    const button = modal.locator(`button:has-text("${buttonText}"), input[value="${buttonText}"]`).last();
    await expect(button).toBeVisible();
    await button.click();
    await this.page.waitForTimeout(500);
  }
}
