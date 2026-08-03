import { Locator, Page, expect } from '@playwright/test';
import { BackendPage as BaseBackendPage } from '../shared/fixtures/index';
import config from '../config';

/**
 * in2publish-specific BackendPage.
 * Extends the shared BackendPage with the project config.
 */
export class BackendPage extends BaseBackendPage {
  private static readonly FILE_STORAGE_ROOT_IDENTIFIER = '1:/';

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
   * Navigate through the file storage tree by selecting each path segment.
   * Used for Filelist and Publish Files modules.
   *
   * Nodes are addressed by their storage identifier (data-id) rather than by label, because folder names are not
   * unique across the tree: after publishing a moved folder it exists at the old and at the new location, and
   * matching by label would select whichever node comes first in the tree.
   *
   * @param pathSegments Folder names starting at the storage root
   *                     (e.g., ['fileadmin', 'Testcases', '2b_published_file'])
   */
  async selectInFileStorageTree(pathSegments: string[]): Promise<void> {
    const fileTree = this.page.locator('.scaffold-content-navigation-component');
    await expect(fileTree).toBeVisible({ timeout: 10000 });

    let identifier = BackendPage.FILE_STORAGE_ROOT_IDENTIFIER;

    for (const [index, segment] of pathSegments.entries()) {
      if (index > 0) {
        identifier += `${segment}/`;
      }

      const treeNode = fileTree.locator(`[data-id="${encodeURIComponent(identifier)}"]`);
      await expect(treeNode).toBeVisible({ timeout: 10000 });
      await this.expandFileStorageTreeNode(treeNode);
      await this.selectFileStorageTreeNode(treeNode);
    }

    await this.page.waitForTimeout(1000);
  }

  /**
   * Expand a file storage tree node so that its children become addressable.
   */
  private async expandFileStorageTreeNode(treeNode: Locator): Promise<void> {
    const chevron = treeNode.locator('.node-toggle');
    const isExpandable = await chevron.count() > 0;
    const isExpanded = await treeNode.getAttribute('aria-expanded') === 'true';

    if (isExpandable === true && isExpanded === false) {
      await chevron.click();
      await this.page.waitForTimeout(500);
    }
  }

  /**
   * Select a file storage tree node to load its content into the module.
   */
  private async selectFileStorageTreeNode(treeNode: Locator): Promise<void> {
    const label = treeNode.locator('.node-contentlabel').first();
    await expect(label).toBeVisible({ timeout: 5000 });
    await label.scrollIntoViewIfNeeded();
    await label.click({ force: true });
    await this.page.waitForTimeout(500);
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
