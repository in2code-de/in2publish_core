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

    }

    await expect(moduleLink).toBeVisible({ timeout: 30000 });
    const modulePath = await this.resolveModulePath(moduleLink);

    // Clicking the ARIA menu item can update TYPO3's active menu state without navigating the content iframe.
    // Use the actual module link and retry once when the iframe remains on the previous module (usually Dashboard).
    for (let attempt = 1; attempt <= 2; attempt++) {
      await moduleLink.click({ timeout: 30000 });
      try {
        await this.waitForModuleDocument(modulePath, attempt === 1 ? 15000 : 45000);
        break;
      } catch (error) {
        if (attempt === 2) {
          throw error;
        }
      }
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

  async openContextMenuOfSelectedPageInPageTree(): Promise<void> {
      const treeItem = this.page.locator(
          'typo3-backend-navigation-component-pagetree .node-selected',
      ).first();

      const clickableLabel = treeItem.locator('.node-contentlabel').first();

      await clickableLabel.scrollIntoViewIfNeeded();
      await clickableLabel.click({ button: 'right'});
      await this.page.waitForTimeout(500);
  }

  async clickPageTreeContextMenuItem(optionText: string): Promise<void> {
      const contextMenu = this.page.locator('typo3-backend-context-menu');
      await expect(contextMenu).toContainText(optionText);

      const button = contextMenu.locator('[aria-label="' + optionText + '"]');
      await expect(button).toBeVisible();
      await button.click();
      await this.page.waitForTimeout(500);
  }
}
