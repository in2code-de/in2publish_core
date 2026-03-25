import { Browser, Page, expect } from '@playwright/test';
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
   * Navigate through the file storage tree by clicking each path segment.
   * Used for Filelist and Publish Files modules.
   * @param pathSegments Array of folder names to navigate through (e.g., ['fileadmin', 'Testcases', '2b_published_file'])
   */
  async selectInFileStorageTree(pathSegments: string[]): Promise<void> {
    const fileTree = this.page.locator('.scaffold-content-navigation-component');
    await expect(fileTree).toBeVisible({ timeout: 10000 });

    // Track the expected aria-level for disambiguation when duplicate folder names exist.
    // TYPO3's tree renders flat DOM elements with aria-level attributes.
    let expectedLevel: number | null = null;

    for (const segment of pathSegments) {
      let candidates;
      if (expectedLevel !== null) {
        // Use aria-level to ensure we pick a child of the previously expanded node,
        // not a sibling with the same name at a different tree depth.
        candidates = fileTree.locator(`[role="treeitem"][aria-level="${expectedLevel}"]`)
          .filter({ hasText: segment });
      } else {
        candidates = fileTree.locator(`[role="treeitem"]`).filter({ hasText: segment });
      }

      const firstNode = candidates.first();
      await expect(firstNode).toBeVisible({ timeout: 10000 });

      // Read the actual level for determining the next expected child level
      const levelStr = await firstNode.getAttribute('aria-level');
      expectedLevel = levelStr ? parseInt(levelStr, 10) + 1 : null;

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
   * Create a Foreign backend context, log in, run a callback, then close the context.
   * Handles context cleanup even if the callback throws.
   *
   * @param browser The Playwright Browser fixture
   * @param callback Function receiving the logged-in foreign BackendPage
   */
  async withForeignContext<T>(browser: Browser, callback: (foreignBackend: BackendPage) => Promise<T>): Promise<T> {
    const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
    try {
      const foreignPage = await foreignContext.newPage();
      const foreignBackend = new BackendPage(foreignPage);
      await foreignBackend.login(config.foreign.baseUrl);
      return await callback(foreignBackend);
    } finally {
      await foreignContext.close();
    }
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
