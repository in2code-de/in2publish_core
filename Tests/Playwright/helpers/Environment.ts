import { createEnvironment } from '@in2code/typo3-playwright/helpers';
import * as path from 'path';

/**
 * in2publish_core environment helper.
 * Runs 'make restore-core-only' from the monorepo root to reset core tables and fileadmin.
 *
 * The Playwright container workdir is packages/in2publish_core/, so we resolve
 * five levels up from Tests/Playwright/helpers/ to reach the monorepo root.
 */
export const Environment = createEnvironment({
  command: 'make restore-core-only',
  cwd: path.resolve(__dirname, '../../../../..'),
  skipInCi: true,
});
