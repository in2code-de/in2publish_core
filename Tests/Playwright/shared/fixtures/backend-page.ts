import { expect } from '../playwright';
import type { Locator, Page } from '../playwright';
import { backendLogin } from '../helpers/backend-login.helper';
import { Typo3TestConfig } from '../types';

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

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
      const exactText = new RegExp(`^\\s*${escapeRegExp(segment)}\\s*$`);
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
    const activeOverlay = this.contentFrame.locator('.in2publish-loading-overlay--active');
    const successPattern = /Successfully published\.|has been published (successfully|to the foreign system\.)/;
    const frameSuccessMessage = this.contentFrame.getByText(successPattern).first();
    const pageSuccessMessage = this.page.getByText(successPattern).first();
    const frameErrorMessage = this.contentFrame.locator('.alert-danger, .callout-danger, .alert-error').first();
    const pageErrorMessage = this.page.locator('.alert-danger, .callout-danger, .alert-error').first();

    // The inactive overlay is hidden before publishing starts, so its hidden state cannot signal
    // completion. Wait for an explicit result and then ensure that a started overlay is gone.
    const completionSignal = await Promise.race([
      frameSuccessMessage.waitFor({ state: 'visible', timeout: 120000 }).then(() => 'success'),
      pageSuccessMessage.waitFor({ state: 'visible', timeout: 120000 }).then(() => 'success'),
      frameErrorMessage.waitFor({ state: 'visible', timeout: 120000 }).then(() => 'error'),
      pageErrorMessage.waitFor({ state: 'visible', timeout: 120000 }).then(() => 'error'),
    ]);

    if (completionSignal === 'error') {
      const errorText = (await frameErrorMessage.textContent())
        || (await pageErrorMessage.textContent())
        || 'unknown publishing error';
      throw new Error(`Publishing failed: ${errorText}`);
    }

    await activeOverlay.waitFor({ state: 'hidden', timeout: 10000 });
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
