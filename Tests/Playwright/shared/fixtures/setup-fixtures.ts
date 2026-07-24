import { expect, test as base } from '../playwright';
import type { Page } from '../playwright';

type BackendPageConstructor<T> = new (page: Page) => T;

export function createBackendTest<T>(BackendPageClass: BackendPageConstructor<T>) {
  return base.extend<{ backend: T }>({
    backend: async ({ page }, use) => {
      const backend = new BackendPageClass(page);
      await use(backend);
    },
  });
}

export { expect };
