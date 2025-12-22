import { Page, Frame, Locator, expect } from '@playwright/test';

export class Typo3Helper {
    readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    /**
     * Login to TYPO3 backend
     * @param username Default: 'admin'
     * @param password Default: 'password'
     */
    async backendLogin(username: string = 'admin', password: string = 'password'): Promise<void> {
        await this.page.goto('/typo3', { waitUntil: 'domcontentloaded' });

        // Wait a moment for page to render
        await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});

        // Check if we are already logged in by looking for the scaffold
        const isAlreadyLoggedIn = await this.page.locator('.scaffold-header')
            .isVisible({ timeout: 2000 })
            .catch(() => false);

        if (isAlreadyLoggedIn) {
            return; // Already logged in, nothing to do
        }

        // Look for login form
        const loginForm = this.page.locator('#typo3-login-form');
        await expect(loginForm).toBeVisible({ timeout: 5000 });

        // Fill in credentials
        await this.page.fill('#t3-username', username);
        await this.page.fill('#t3-password', password);
        await this.page.click('#t3-login-submit');

        // Wait for login to complete - backend should load
        await expect(this.page.locator('.scaffold-header')).toBeVisible({ timeout: 15000 });

        // Wait for backend to be fully ready
        await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
    }

    async selectModule(moduleName: string) {
        // Mimic the PHP implementation:
        // //nav[@id='modulemenu']//span[@class='modulemenu-name' and text()='$text']/ancestor::a
        const moduleLink = this.page.locator('#modulemenu .modulemenu-name', { hasText: moduleName }).locator('xpath=ancestor::a');

        // Get the partial URL we expect to navigate to (e.g. /typo3/module/in2publish_core/m4)
        // We can use the href attribute, but strip the token
        const href = await moduleLink.getAttribute('href');
        let targetRoute = '';
        if (href) {
            const url = new URL(href, this.page.url());
            targetRoute = url.pathname; // This should be unique enough, e.g. /typo3/module/in2publish_core/m4
        }

        await moduleLink.click();

        await this.ensureContentIframeLoaded();

        if (targetRoute) {
            const frame = await this.getContentFrame();
            // Wait for the frame to navigate to the target route
            await expect.poll(() => {
                return frame.url();
            }, { timeout: 10000 }).toContain(targetRoute);

            // Wait for network to be idle and DOM to be loaded
            await frame.waitForLoadState('domcontentloaded');
            await frame.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {
                // Network idle might timeout for long-polling connections, that's OK
            });

            // Some modules (like Content Publisher) load content in a nested list_frame
            // Poll until the actual content frame exists and has content
            await expect.poll(async () => {
                const actualContentFrame = await this.getContentFrame();
                const bodyText = await actualContentFrame.locator('body').textContent();
                return bodyText && bodyText.trim().length > 100;
            }, { timeout: 10000 }).toBeTruthy();
        }
    }

    async ensureContentIframeLoaded() {
        // Helper to wait for the iframe to be present
        const iframeElement = this.page.locator('iframe#typo3-contentIframe').first();
        await expect(iframeElement).toBeVisible({ timeout: 15000 });
    }

    /**
     * Gets the TYPO3 content iframe
     * @returns The content frame
     * @throws Error if the content iframe is not found
     */
    async getContentFrame(): Promise<Frame> {
        // Try list_frame first (used by Content Publisher module)
        let frame = this.page.frame({ name: 'list_frame' });
        if (frame) return frame;

        // Try primary iframe (used by most TYPO3 modules)
        frame = this.page.frame({ name: 'typo3-contentIframe' });
        if (frame) return frame;

        // Try by URL pattern as fallback - must have token parameter to avoid matching container frame
        frame = this.page.frame({ url: /typo3\/module.*token=/ });
        if (frame) return frame;

        throw new Error('Content iframe not found. Available frames: ' +
            this.page.frames().map(f => `${f.name()} (${f.url()})`).join(', '));
    }

    /**
     * Check if user is already logged into the TYPO3 backend
     * @returns true if logged in, false otherwise
     */
    async isLoggedIn(): Promise<boolean> {
        return await this.page.locator('.scaffold-header').isVisible({ timeout: 1000 }).catch(() => false);
    }

    /**
     * Wait for network activity to settle
     * @param timeout Maximum time to wait in milliseconds (default: 5000)
     */
    async waitForNetworkIdle(timeout: number = 5000): Promise<void> {
        await this.page.waitForLoadState('networkidle', { timeout }).catch(() => {
            // Ignore timeout, some pages have long-polling connections
        });
    }

    /**
     * Wait for a specific element in the content frame
     * @param selector The element selector
     * @param options Wait options
     */
    async waitForElementInContentFrame(selector: string, options?: { timeout?: number }): Promise<Locator> {
        const frame = await this.getContentFrame();
        const element = frame.locator(selector);
        await element.waitFor({ state: 'visible', ...options });
        return element;
    }

    /**
     * Check for TYPO3 flash messages in the content frame
     * @param type The message type: 'success', 'warning', 'error', 'info'
     * @returns true if messages of the specified type exist
     */
    async hasFlashMessages(type: 'success' | 'warning' | 'error' | 'info'): Promise<boolean> {
        const frame = await this.getContentFrame();
        const classMap = {
            success: '.callout-success',
            warning: '.callout-warning',
            error: '.callout-danger',
            info: '.callout-info'
        };
        const count = await frame.locator(classMap[type]).count();
        return count > 0;
    }
}
