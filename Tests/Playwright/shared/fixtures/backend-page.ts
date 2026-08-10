import { expect } from '../playwright';
import type { Locator, Page } from '../playwright';
import { backendLogin } from '../helpers/backend-login.helper';
import { Typo3TestConfig } from '../types';

export class BackendPage {
  readonly moduleNavigation;
  readonly contentFrame;

  constructor(
    protected readonly page: Page,
    protected readonly config: Typo3TestConfig,
  ) {
    this.moduleNavigation = this.page.locator('#modulemenu');
    this.contentFrame = this.page.frameLocator('#typo3-contentIframe');
  }

  async login(backendUrl?: string): Promise<void> {
    await backendLogin(this.page, this.config, { url: backendUrl });
  }

  async gotoModule(moduleName: string): Promise<void> {
    const moduleLink = this.page.locator(`#modulemenu a.modulemenu-action[title="${moduleName}"]`);
    const modulePath = await this.resolveModulePath(moduleLink);

    await moduleLink.click({ timeout: 30000 });

    await expect(this.page.locator('iframe#typo3-contentIframe')).toBeVisible({ timeout: 45000 });
    await expect(moduleLink).toHaveClass(/modulemenu-action-active/, { timeout: 30000 });
    await this.waitForModuleDocument(modulePath);
    await this.page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
    await this.page.waitForTimeout(1000);
    await this.page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  }

  /**
   * Resolve the path the given module menu link navigates the content iframe to.
   */
  protected async resolveModulePath(moduleLink: Locator): Promise<string> {
    const moduleHref = await moduleLink.getAttribute('href', { timeout: 30000 });

    return new URL(moduleHref ?? '', this.page.url()).pathname;
  }

  /**
   * Wait until the content iframe really shows the module reachable under the given path.
   *
   * The content iframe element survives module switches, so its visibility says nothing about which module is
   * rendered inside it. Without waiting for the embedded document itself, assertions can still run against the
   * old module.
   */
  protected async waitForModuleDocument(modulePath: string, timeout: number = 45000): Promise<void> {
    await this.page.waitForFunction(
      (expectedPath) => {
        const iframe = document.querySelector('iframe#typo3-contentIframe') as HTMLIFrameElement | null;
        const iframeDocument = iframe?.contentDocument ?? null;

        return iframeDocument !== null
          && iframeDocument.readyState === 'complete'
          && iframeDocument.location.pathname === expectedPath;
      },
      modulePath,
      { timeout },
    );
  }

  async searchInPageTreeAndSelectFirstOccurrence(searchText: string): Promise<void> {
    const pageTree = this.page.locator('.scaffold-content-navigation-component');
    await expect(pageTree).toBeVisible({ timeout: 30000 });

    const searchInput = this.page.locator('input[placeholder="Enter search term"]');
    await expect(searchInput).toBeVisible({ timeout: 30000 });
    await searchInput.clear({ timeout: 30000 });
    await searchInput.fill(searchText, { timeout: 30000 });
    await searchInput.press('Enter', { timeout: 30000 });
    await this.page.waitForTimeout(800);
    await this.page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

    const treeItems = this.page.locator('[role="treeitem"]');
    const matchingTreeItems = treeItems.filter({ hasText: searchText });
    const count = await matchingTreeItems.count();

    if (count === 0) {
      throw new Error(`No page tree nodes found matching "${searchText}"`);
    }

    const firstTreeItem = matchingTreeItems.first();
    await expect(firstTreeItem).toBeVisible({ timeout: 30000 });
    await firstTreeItem.waitFor({ state: 'visible', timeout: 15000 });
    await this.page.waitForTimeout(500);

    const clickableElement = firstTreeItem.locator('.node-contentlabel').first();
    await expect(clickableElement).toBeVisible({ timeout: 15000 });
    await clickableElement.scrollIntoViewIfNeeded({ timeout: 15000 });
    await this.page.waitForTimeout(300);
    await clickableElement.click({ force: true, timeout: 30000 });
    await this.page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await this.page.waitForTimeout(1500);
  }
}
