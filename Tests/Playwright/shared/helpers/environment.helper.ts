import type { Page } from '../playwright';
import { execMake } from './make.helper';

const BLANK_PAGE = 'about:blank';

/**
 * Reset the test environment through a Makefile target.
 *
 * The restore drops and recreates every table listed in the dump preamble, so for a few seconds tables like 'pages'
 * or 'be_users' do not exist. A TYPO3 backend page that is still open keeps polling in the background and answers
 * such a request with a TableNotFoundException, which then surfaces as an unrelated assertion failure in whichever
 * test runs next. This is particularly noticeable in UI mode, where pages stay alive between runs.
 *
 * Parking every open page on about:blank and closing leftover contexts (a foreign backend window of a test that
 * failed before its cleanup) guarantees that no request can reach TYPO3 while the databases are being rebuilt.
 *
 * @param page       The active page, used to reach the browser and its contexts
 * @param makeTarget Make target controlling whether only database state or fileadmin is reset
 */
export async function resetEnvironment(page: Page, makeTarget: string = 'playwright-reset'): Promise<void> {
  const activeContext = page.context();

  for (const context of activeContext.browser()?.contexts() ?? []) {
    if (context !== activeContext) {
      await context.close();
    }
  }

  for (const openPage of activeContext.pages()) {
    if (openPage.isClosed() === false) {
      await openPage.goto(BLANK_PAGE);
    }
  }

  execMake(makeTarget);
}
