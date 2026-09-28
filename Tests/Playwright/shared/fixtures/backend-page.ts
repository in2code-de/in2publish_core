import { expect } from '../playwright';
import type { Locator, Page } from '../playwright';
import { backendLogin } from '../helpers/backend-login.helper';
import { Typo3TestConfig } from '../types';

export class BackendPage {
  private static readonly SHARED_FILE_STORAGE_ROOT_IDENTIFIER = '1:/';

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
    const moduleHref = await moduleLink.getAttribute('href', { timeout: 30000 });
    const modulePath = new URL(moduleHref ?? '', this.page.url()).pathname;

    await moduleLink.click({ timeout: 30000 });

    await expect(this.page.locator('iframe#typo3-contentIframe')).toBeVisible({ timeout: 45000 });
    await expect(moduleLink).toHaveClass(/modulemenu-action-active/, { timeout: 30000 });

    // The content iframe element survives module switches, so its visibility says nothing about which module is
    // rendered inside it. Without waiting for the embedded document itself, assertions can still run against the
    // old module.
    await this.page.waitForFunction(
      (expectedPath) => {
        const iframe = document.querySelector('iframe#typo3-contentIframe') as HTMLIFrameElement | null;
        const iframeDocument = iframe?.contentDocument ?? null;

        return iframeDocument !== null
          && iframeDocument.readyState === 'complete'
          && iframeDocument.location.pathname === expectedPath;
      },
      modulePath,
      { timeout: 45000 },
    );
    await this.page.waitForTimeout(1000);
  }

  /**
   * Navigate through the file storage tree by storage identifier.
   */
  async selectInFileStorageTree(pathSegments: string[]): Promise<void> {
    const fileTree = this.page.locator('.scaffold-content-navigation-component');
    await expect(fileTree).toBeVisible({ timeout: 10000 });

    let identifier = BackendPage.SHARED_FILE_STORAGE_ROOT_IDENTIFIER;

    for (const [index, segment] of pathSegments.entries()) {
      if (index > 0) {
        identifier += `${segment}/`;
      }

      const treeNode = fileTree.locator(`[data-id="${encodeURIComponent(identifier)}"]`);
      await expect(treeNode).toBeVisible({ timeout: 10000 });
      await this.expandSharedFileStorageTreeNode(treeNode);
      await this.selectSharedFileStorageTreeNode(treeNode, identifier);
    }
  }

  private async expandSharedFileStorageTreeNode(treeNode: Locator): Promise<void> {
    const chevron = treeNode.locator('.node-toggle');
    const isExpandable = await chevron.count() > 0;
    const isExpanded = await treeNode.getAttribute('aria-expanded') === 'true';

    if (isExpandable && !isExpanded) {
      await chevron.click();
      await this.page.waitForTimeout(500);
    }
  }

  private async selectSharedFileStorageTreeNode(treeNode: Locator, identifier: string): Promise<void> {
    const label = treeNode.locator('.node-contentlabel').first();
    await expect(label).toBeVisible({ timeout: 5000 });
    await label.scrollIntoViewIfNeeded();

    const navigation = this.page.waitForResponse(
      (response) => response.request().isNavigationRequest()
        && new URL(response.url()).searchParams.get('id') === identifier,
      { timeout: 30000 },
    );

    await label.click({ force: true });
    await navigation;
    await this.waitUntilSharedContentFrameShowsFolder(identifier);
  }

  private async waitUntilSharedContentFrameShowsFolder(identifier: string): Promise<void> {
    await this.page.waitForFunction(
      (expectedIdentifier) => {
        const iframe = document.querySelector('iframe#typo3-contentIframe') as HTMLIFrameElement | null;
        const iframeDocument = iframe?.contentDocument ?? null;

        return iframeDocument !== null
          && iframeDocument.readyState === 'complete'
          && new URL(iframeDocument.location.href).searchParams.get('id') === expectedIdentifier;
      },
      identifier,
      { timeout: 30000 },
    );
  }

  async waitUntilPublishingFinished(): Promise<void> {
    const activeOverlay = this.contentFrame.locator('.in2publish-loading-overlay--active');
    const successPattern = /Successfully published\.|has been published (successfully|to the foreign system\.)/;
    const frameSuccessMessage = this.contentFrame.getByText(successPattern).first();
    const pageSuccessMessage = this.page.getByText(successPattern).first();
    const frameErrorMessage = this.contentFrame.locator('.alert-danger, .callout-danger, .alert-error').first();
    const pageErrorMessage = this.page.locator('.alert-danger, .callout-danger, .alert-error').first();

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

  async clickModalButton(buttonText: string): Promise<void> {
    const modal = this.page.locator('typo3-backend-modal .modal, .modal.show').last();
    await expect(modal).toBeVisible({ timeout: 10000 });
    const button = modal.locator(`button:has-text("${buttonText}"), input[value="${buttonText}"]`).last();
    await expect(button).toBeVisible();
    await button.click();
    await this.page.waitForTimeout(500);
  }

  async searchInPageTreeAndSelectFirstOccurrence(searchText: string): Promise<void> {
    await this.searchInPageTreeAndSelectOccurrence(searchText, 0);
  }

  async searchInPageTreeAndSelectOccurrence(searchText: string, occurrence: number): Promise<void> {
    const pageTree = this.page.locator('.scaffold-content-navigation-component');
    await expect(pageTree).toBeVisible({ timeout: 30000 });

    const searchInput = this.page.locator('input[placeholder="Enter search term"]');
    await expect(searchInput).toBeVisible({ timeout: 30000 });
    await searchInput.clear({ timeout: 30000 });
    await searchInput.fill(searchText, { timeout: 30000 });
    await searchInput.press('Enter', { timeout: 30000 });

    const treeItems = this.page.locator('[role="treeitem"]');
    const matchingTreeItems = treeItems.filter({ hasText: searchText });
    await expect.poll(
      () => matchingTreeItems.count(),
      { message: `Wait for occurrence ${occurrence} of "${searchText}" in the page tree`, timeout: 30000 },
    ).toBeGreaterThan(occurrence);

    const treeItem = matchingTreeItems.nth(occurrence);
    await expect(treeItem).toBeVisible({ timeout: 30000 });
    await treeItem.waitFor({ state: 'visible', timeout: 15000 });
    await this.page.waitForTimeout(500);

    const clickableElement = treeItem.locator('.node-contentlabel').first();
    await expect(clickableElement).toBeVisible({ timeout: 15000 });
    await clickableElement.scrollIntoViewIfNeeded({ timeout: 15000 });
    await this.page.waitForTimeout(300);
    await clickableElement.click({ force: true, timeout: 30000 });
    await this.page.waitForTimeout(1500);
  }
}
