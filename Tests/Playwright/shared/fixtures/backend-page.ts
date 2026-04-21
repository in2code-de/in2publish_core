import { expect } from '../playwright';
import type { Page } from '../playwright';
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
    await moduleLink.click();

    await expect(this.page.locator('iframe#typo3-contentIframe')).toBeVisible({ timeout: 15000 });
    await expect(moduleLink).toHaveClass(/modulemenu-action-active/, { timeout: 10000 });
    await this.page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
    await this.page.waitForTimeout(1000);
    await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
  }

  async searchInPageTreeAndSelectFirstOccurrence(searchText: string): Promise<void> {
    const pageTree = this.page.locator('.scaffold-content-navigation-component');
    await expect(pageTree).toBeVisible({ timeout: 10000 });

    const searchInput = this.page.locator('input[placeholder="Enter search term"]');
    await expect(searchInput).toBeVisible({ timeout: 10000 });
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
    await firstTreeItem.waitFor({ state: 'visible', timeout: 5000 });
    await this.page.waitForTimeout(500);

    const clickableElement = firstTreeItem.locator('.node-contentlabel').first();
    await expect(clickableElement).toBeVisible({ timeout: 5000 });
    await clickableElement.scrollIntoViewIfNeeded();
    await this.page.waitForTimeout(300);
    await clickableElement.click({ force: true });
    await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
    await this.page.waitForTimeout(1500);
  }
}
