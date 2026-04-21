import { createEnvironment } from '../shared/helpers/index';
import * as path from 'path';

export const Environment = createEnvironment({
  command: 'make restore',
  cwd: path.resolve(__dirname, '../../..'),
  skipInCi: true,
});
