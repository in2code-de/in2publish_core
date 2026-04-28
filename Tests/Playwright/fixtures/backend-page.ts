import { Page, expect } from '@playwright/test';
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

  private escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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
    const moduleIdentifiers: Record<string, string> = {
      'Publish Overview': 'in2publish_core_m1',
      'Publish Files': 'in2publish_core_m3',
      'Publisher Tools': 'in2publish_core_m4',
      'Publish Redirects': 'in2publish_core_m5',
      'Publish Workflow': 'in2publish_m2',
      'Compare pages': 'in2publish_m5',
    };
    const moduleGroups: Record<string, string> = {
      'Page': 'Content',
      'List': 'Content',
      'Publish Overview': 'Content',
      'Filelist': 'Media',
      'Publish Files': 'Media',
      'Publish Redirects': 'Sites',
      'Publisher Tools': 'Administration',
    };
    const resolvedName = v14ModuleNames[moduleName] || moduleName;
    const moduleLink = moduleIdentifiers[moduleName]
      ? this.page.locator(`#modulemenu a.modulemenu-action[data-moduleroute-identifier="${moduleIdentifiers[moduleName]}"]`).first()
      : this.page.locator(`#modulemenu a.modulemenu-action[title="${resolvedName}"]`).first();
    const visibleMenuItem = this.page.getByRole('menuitem', { name: resolvedName, exact: true }).first();
    const parentGroup = moduleGroups[moduleName];

    await expect(this.page.locator('#typo3-contentIframe')).toBeVisible({ timeout: 45000 });

    if (parentGroup) {
      const groupToggle = this.page
        .getByRole('menubar', { name: 'Module Menu' })
        .getByRole('menuitem', { name: parentGroup, exact: true })
        .first();
      const groupMenu = this.page.getByRole('menu', { name: parentGroup });
      if (!await groupMenu.isVisible().catch(() => false)) {
        await groupToggle.click({ timeout: 30000 });
      }

      const groupedMenuItem = groupMenu.getByRole('menuitem', { name: resolvedName, exact: true }).first();
      if (await groupedMenuItem.isVisible().catch(() => false)) {
        await groupedMenuItem.click({ timeout: 30000 });
      } else if (moduleIdentifiers[moduleName] && await visibleMenuItem.isVisible().catch(() => false)) {
        await visibleMenuItem.click({ timeout: 30000 });
      } else {
        await moduleLink.click({ timeout: 30000 });
      }
    } else if (moduleIdentifiers[moduleName] && await visibleMenuItem.isVisible().catch(() => false)) {
      await visibleMenuItem.click({ timeout: 30000 });
    } else {
      await moduleLink.click({ timeout: 30000 });
    }

    // TYPO3 v14 loads modules in the content iframe — verify the iframe src
    // contains the module path from the menu link's href.
    const href = await moduleLink.getAttribute('href');
    if (href) {
      const modulePath = href.split('?')[0];
      const iframe = this.page.locator('#typo3-contentIframe');
      await expect(iframe).toHaveAttribute('src', new RegExp(modulePath.replace(/[/]/g, '\\/')), { timeout: 45000 });
    }

    await expect(this.page.locator('#typo3-contentIframe')).toBeAttached({ timeout: 45000 });
    await this.contentFrame.locator('body').waitFor({ state: 'visible', timeout: 45000 });
    await this.page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  }

  /**
     * Search for a page in the page tree with retry logic.
     * Optimized for TYPO3 v14 Web Components and Shadow DOM.
     */
  async searchInPageTreeAndSelectFirstOccurrence(searchText: string): Promise<void> {
    const expandToggle = this.page.locator(
      'typo3-backend-content-navigation-toggle[action="expand"], button[title="Show navigation"], button[aria-label="Show navigation"]',
    ).first();
    if (await expandToggle.isVisible().catch(() => false)) {
      await expandToggle.click();
    }

    const treeRoot = this.page.locator(
      'typo3-backend-navigation-component-pagetree, typo3-backend-content-navigation, .scaffold-content-navigation-component, [role="tree"]',
    ).first();
    await expect(treeRoot).toBeVisible({ timeout: 30000 });

    const searchInput = this.page.locator(
      'input#toolbarSearch, input[placeholder="Search term"], input[placeholder="Enter search term"]',
    ).first();
    await expect(searchInput).toBeVisible({ timeout: 30000 });
    await searchInput.fill(searchText);
    await searchInput.press('Enter');

    const exactText = new RegExp(`^\\s*${this.escapeRegExp(searchText)}\\s*$`);
    const treeItem = treeRoot
      .locator('[role="treeitem"]')
      .filter({
        has: this.page.locator('.node-contentlabel').filter({ hasText: exactText }),
      })
      .first();

    await expect(treeItem).toBeVisible({ timeout: 15000 });

    const clickableLabel = treeItem.locator('.node-contentlabel').first();
    if (await clickableLabel.isVisible().catch(() => false)) {
      await clickableLabel.scrollIntoViewIfNeeded();
      await clickableLabel.click({ force: true });
    } else {
      await treeItem.click({ force: true });
    }

    await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
  }

  /**
   * Navigate through the file storage tree by clicking each path segment.
   * Used for Filelist and Publish Files modules.
   * @param pathSegments Array of folder names to navigate through (e.g., ['fileadmin', 'Testcases', '2b_published_file'])
   */
  async selectInFileStorageTree(pathSegments: string[]): Promise<void> {
    const fileTree = this.page.locator(
      'typo3-backend-content-navigation, .scaffold-content-navigation-component, [role="tree"]',
    ).first();
    await expect(fileTree).toBeVisible({ timeout: 10000 });

    for (const segment of pathSegments) {
      const exactText = new RegExp(`^\\s*${this.escapeRegExp(segment)}\\s*$`);
      const treeNode = fileTree.locator('[role="treeitem"]').filter({
        has: this.page.locator('.node-contentlabel').filter({ hasText: exactText }),
      });
      const firstNode = treeNode.first();
      await expect(firstNode).toBeVisible({ timeout: 10000 });

      // Expand the node if it has children and is not expanded
      const chevron = firstNode.locator('.node-toggle');
      if (await chevron.count() > 0) {
        const isExpanded = await firstNode.getAttribute('aria-expanded');
        if (isExpanded !== 'true') {
          await chevron.click();
          await expect(firstNode).toHaveAttribute('aria-expanded', 'true', { timeout: 5000 });
        }
      }

      // Click the label to select the folder
      const label = firstNode.locator('.node-contentlabel').first();
      await expect(label).toBeVisible({ timeout: 5000 });
      await label.scrollIntoViewIfNeeded();
      await label.click({ force: true });
      await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
    }
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
