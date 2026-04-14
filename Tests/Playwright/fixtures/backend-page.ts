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
   * Overrides base class to handle TYPO3 v14 module renames.
   *
   * TYPO3 v14 loads modules inside #typo3-contentIframe — the main page URL
   * does not change. So we click the menu link and verify the iframe src.
   */
  async gotoModule(moduleName: string): Promise<void> {
    const v14ModuleNames: Record<string, string> = {
      'Page': 'Layout',
      'List': 'Records',
      'Filelist': 'Media',
    };
    const resolvedName = v14ModuleNames[moduleName] || moduleName;
    const moduleLink = this.page.locator(`#modulemenu a.modulemenu-action[title="${resolvedName}"]`);

    await moduleLink.click({ timeout: 5000 });

    // TYPO3 v14 loads modules in the content iframe — verify the iframe src
    // contains the module path from the menu link's href.
    const href = await moduleLink.getAttribute('href');
    if (href) {
      const modulePath = href.split('?')[0];
      const iframe = this.page.locator('#typo3-contentIframe');
      await expect(iframe).toHaveAttribute('src', new RegExp(modulePath.replace(/[/]/g, '\\/')), { timeout: 10000 });
    }

    await expect(this.page.locator('#typo3-contentIframe')).toBeAttached({ timeout: 15000 });
    await this.page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await this.page.waitForTimeout(1000);
  }

  /**
     * Search for a page in the page tree with retry logic.
     * Optimized for TYPO3 v14 Web Components and Shadow DOM.
     */
    async searchInPageTreeAndSelectFirstOccurrence(searchText: string): Promise<void> {
      const maxRetries = 3;

      for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
          // 1. Ensure the navigation container is present
          const navContainer = this.page.locator('typo3-backend-content-navigation');
          await expect(navContainer).toBeAttached({ timeout: 15000 });

          // 2. Handle Collapsed State:
          // We look for the toggle component. If the action is 'expand', it's collapsed.
          const expandToggle = this.page.locator('typo3-backend-content-navigation-toggle[action="expand"]');
          if (await expandToggle.isVisible()) {
            console.log(`[Attempt ${attempt}] Navigation collapsed. Expanding...`);
            await expandToggle.click();
            // Short wait for the CSS transition (width 0 -> 300px)
            await this.page.waitForTimeout(500);
          }

          // 3. Target the Page Tree component and its search input
          // Prefixing with the component tag helps Playwright pierce nested Shadow Roots
          const treeComponent = this.page.locator('typo3-backend-navigation-component-pagetree');
          const searchInput = treeComponent.locator('input#toolbarSearch');

          // Wait for the input to be ready for interaction
          await searchInput.waitFor({ state: 'visible', timeout: 15000 });

          // Using fill() is safer for Lit components than type() to avoid event race conditions
          await searchInput.fill(searchText);
          await searchInput.press('Enter');

          // 4. Locate results within the Shadow DOM
          // We filter by text and look for the standard [role="treeitem"]
          const treeItem = treeComponent.locator('[role="treeitem"]').filter({ hasText: searchText }).first();

          // Increased timeout here as the tree filtering can be slow on large installations
          await expect(treeItem).toBeVisible({ timeout: 15000 });

          // 5. Click the actual label element
          // .node-contentlabel is the standard TYPO3 class for the clickable text area
          const clickableLabel = treeItem.locator('.node-contentlabel').first();
          await clickableLabel.scrollIntoViewIfNeeded();
          await clickableLabel.click({ force: true });

          // Wait for TYPO3 to finish loading the content on the right side
          await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
          // Allow TYPO3 to commit page selection state before the caller switches to another module
          await this.page.waitForTimeout(500);

          return; // Success!

        } catch (error) {
          console.warn(`[Attempt ${attempt}] Search failed for "${searchText}": ${error.message}`);

          if (attempt === maxRetries) {
            throw error;
          }

          // Recovery: Instead of a full reload(), we do a "soft" navigation to the current URL.
          // This resets the component state without getting stuck on the 'load' event.
          await this.page.goto(this.page.url(), { waitUntil: 'commit', timeout: 15000 }).catch(() => {});

          // Wait longer on each subsequent attempt
          await this.page.waitForTimeout(2000 * attempt);
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
