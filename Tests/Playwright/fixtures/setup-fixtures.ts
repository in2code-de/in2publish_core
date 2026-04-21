import { createBackendTest, expect } from '../shared/fixtures/index';
import { BackendPage } from './backend-page';

export const test = createBackendTest(BackendPage);
export { expect };
