import { createEnvironment } from '@in2code/typo3-playwright/helpers';
import * as path from 'path';

/**
 * in2publish_core environment helper.
 * Runs 'make restore' from the monorepo root to reset database and fileadmin.
 *
 * The Playwright container workdir is packages/in2publish_core/, so we resolve
 * two levels up to reach the root where the Makefile lives.
 */
export const Environment = createEnvironment({
  command: 'make restore-core-only',
  cwd: path.resolve(__dirname, '../../../../..'),
  skipInCi: true,
});
