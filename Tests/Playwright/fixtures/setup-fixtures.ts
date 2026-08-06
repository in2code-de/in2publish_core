import { createBackendTest, expect } from '../shared/fixtures/index';
import { BackendPage } from './backend-page';
import { resetEnvironment } from '../shared/helpers';

const base = createBackendTest(BackendPage);

type EnvironmentFixtures = {
  prepareEnvironment: void;
};

type EnvironmentOptions = {
  autoRestore: boolean;
};

export const test = base.extend<EnvironmentFixtures & EnvironmentOptions>({
  autoRestore: [true, { option: true }],

  prepareEnvironment: [async ({ autoRestore, page }, use) => {
    if (autoRestore) {
      await resetEnvironment(page);
    }
    await use();
  }, { auto: true }],
});

export { expect };
