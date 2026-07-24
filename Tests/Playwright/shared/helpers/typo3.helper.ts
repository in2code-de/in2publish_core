import type { Frame, Page } from '../playwright';

export async function getContentFrame(page: Page): Promise<Frame> {
  let frame = page.frame({ name: 'list_frame' });
  if (frame) {
    return frame;
  }

  frame = page.frame({ name: 'typo3-contentIframe' });
  if (frame) {
    return frame;
  }

  frame = page.frame({ url: /typo3\/module.*token=/ });
  if (frame) {
    return frame;
  }

  throw new Error(
    'Content iframe not found. Available frames: '
      + page.frames().map(currentFrame => `${currentFrame.name()} (${currentFrame.url()})`).join(', '),
  );
}

export async function countFlashMessages(
  frame: Frame,
  type: 'success' | 'warning' | 'error' | 'info',
): Promise<number> {
  const classMap = {
    success: '.callout-success',
    warning: '.callout-warning',
    error: '.callout-danger',
    info: '.callout-info',
  };

  return frame.locator(classMap[type]).count();
}

export async function waitForNetworkIdle(page: Page, timeout: number = 5000): Promise<void> {
  await page.waitForLoadState('networkidle', { timeout }).catch(() => {});
}
