# Playwright Testing for in2publish_core

We have started to migrate our browser tests from Codeception to Playwright to improve stability, speed, and debugging capabilities.
This document contains the documentation for end-to-end browser tests for in2publish_core using Playwright.

## Overview

The test setup runs in Docker. Browser binaries are provided by the Playwright
container and are not installed on the host machine.

## Prerequisites

- Docker must be installed and running
- TYPO3 instances (local/foreign) must be running: `make start`
- No local Node.js installation required

## Quick Start

All Playwright commands must be run from the `packages/in2publish_core` directory.

### Docker workflow

```bash
# Create the container and install the test dependencies inside it
make playwright-install

# Run all tests in headless mode in Docker
make playwright

# Open Playwright UI at http://localhost:<PLAYWRIGHT_UI_PORT>
make playwright-ui

# Run tests with visible browser (headed mode)
make playwright-watch

# Debug tests with Playwright Inspector
make playwright-debug

# View the last test report
make playwright-report

# Run specific test file
make playwright FILE="modules/PublishOverview/publish-changed-content.spec.ts"
```

`PLAYWRIGHT_UI_PORT` is configured in `.env` (currently `9325`).

## Architecture

### Test Structure
```
Tests/Playwright/
├── config.ts                   # Test configuration
├── global.setup.ts             # Global setup (authentication)
├── fixtures/                   # Custom fixtures
│   ├── backend-page.ts         # TYPO3 backend page fixture
│   └── setup-fixtures.ts       # Setup fixtures
├── helpers/                    # Test helpers
│   ├── Environment.ts          # Environment configuration
│   └── Typo3Helper.ts          # TYPO3-specific helpers
└── modules/                    # Test files organized by module
    ├── PublishOverview/
    └── PublisherTools/
```

### Configuration

The test configuration is in `playwright.config.ts` at the project root.

**Key Configuration:**
- Base URL: `https://local.v13.in2publish-core.de/typo3/` (override with `PLAYWRIGHT_BASE_URL`)
- Workers: 1 (sequential execution due to shared database state)
- Retries: 2 on CI, 0 locally
- Timeout: 60 seconds per test

### Environment Isolation

The `in2publish_core` tests operate in an isolated environment (`packages/in2publish_core/.env`).
- **Local System**: `https://local.v13.in2publish-core.de`
- **Foreign System**: `https://foreign.v13.in2publish-core.de`

The test helper `Environment.ts` automatically handles database resets (`make restore`) before tests run.

### Authentication

Tests use authenticated sessions stored in `Tests/Playwright/.auth/login.json`. The authentication is performed once in `global.setup.ts` and reused across all tests for performance.

## Docker Implementation Details

### How It Works

The Docker setup uses a dedicated Playwright service in docker-compose:

1. Playwright runs in Microsoft's official Playwright Docker image
2. The container joins your existing Docker network (`in2publish_core_default`)
3. Tests access your already-running TYPO3 instances (local + foreign) through the network
4. Test files and results are mounted via volume at `/work`
5. The service runs continuously (`sleep infinity`) for fast test execution

### Docker Compose Service

The Playwright service is defined in `.project/docker/docker-compose.*.yaml`:

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `PLAYWRIGHT_BASE_URL` | Base URL for local TYPO3 instance | `https://local.v13.in2publish-core.de/typo3/` |
| `PLAYWRIGHT_FOREIGN_BASE_URL` | Base URL for foreign TYPO3 instance | `https://foreign.v13.in2publish-core.de/typo3/` |
| `CI` | Enable CI mode (retries, forbidOnly) | `0` (set to `1` in CI) |
| `HOST_LOCAL` | Hostname for local instance | From `.env` file |
| `HOST_FOREIGN` | Hostname for foreign instance | From `.env` file |

### Accessing Both Instances in Tests

Tests can access both local and foreign instances using the config helper:

```typescript
import config from '../config';

// Access local instance (default)
await page.goto(config.local.baseUrl + 'module/web/In2publishCoreM1');

// Access foreign instance
await page.goto(config.foreign.baseUrl + 'module/web/In2publishCoreM1');
```

## Writing Tests

see: https://playwright.dev/docs/writing-tests

### Example Test

```typescript
import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';

test.describe('Publish Overview Module', () => {
  test('should display changed records', async ({ page, backend }) => {
    await backend.login();
    await backend.gotoModule('Publish Overview');

    await expect(
      backend.contentFrame.locator('.record-list')
    ).toBeVisible();
  });
});
```

## Debugging

### Playwright UI Mode
The UI mode lets you explore tests, see the DOM snapshot for each step, and debug effectively.
```bash
make playwright-ui
```

**Important:** In Playwright UI, make sure to check the "chromium" project in the project filter at the top to see all tests.

### Trace Viewer
By default, we capture traces on failure. To view the trace of a failed test:
```bash
make playwright-report
```
Click on the failed test and open the "Trace" tab to time-travel through the test execution.

## Troubleshooting

**Tests not visible in UI mode:**
- Make sure the "chromium" project is checked in the project filter at the top of the UI
- The UI remembers your selection in browser localStorage


## Maintenance

### Updating Playwright

Update the npm package and matching Docker image together:
Update the image version in `.project/docker/docker-compose.*.yaml` files:
```yaml
playwright:
  image: mcr.microsoft.com/playwright:v1.XX.X-noble
```

Then recreate the container:
```bash
docker compose up -d playwright
```

### Database Reset

If tests fail due to database state:
```bash
make restore
```

**Note:** In Docker mode (CI=1), the environment reset is skipped since `make` is not available in the Playwright container. 

## Resources

- [Playwright Documentation](https://playwright.dev/)
- [TYPO3 Testing Documentation](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Testing/)
- [Microsoft Playwright Docker Images](https://mcr.microsoft.com/en-us/product/playwright/about)

## Support

For issues or questions:
1. Check this documentation
2. Review the Playwright documentation
3. Check `playwright.config.ts` for configuration
4. Check `.project/docker/docker-compose.*.yaml` for Docker service configuration
5. Ask the team in your communication channel
