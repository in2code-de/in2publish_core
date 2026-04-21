import type { Frame, Page } from '@playwright/test';

const playwright = require(
  require.resolve('@playwright/test', { paths: [process.cwd()] }),
) as typeof import('@playwright/test');

export const expect = playwright.expect;
export const test = playwright.test;

export type { Frame, Page };
