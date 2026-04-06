# Playwright Testing for in2publish_core

We have migrated our browser tests from Codeception to Playwright to improve stability, speed, and debugging capabilities.
This document contains the documentation for end-to-end browser tests for in2publish_core using Playwright.

## Overview

Tests run inside a dedicated Playwright Docker container that connects to the already-running TYPO3 instances (local + foreign) through the Docker network. No local Node.js installation is required.

## Prerequisites

- Docker must be installed and running
- TYPO3 instances (local/foreign) must be running: `make start`

## Quick Start

All Playwright commands must be run from the `packages/in2publish_core` directory (standalone) or from the monorepo root.

### From the package directory (standalone)

```bash
# Run all tests (restores DB, clears caches)
make playwright

# Run specific test file
make playwright FILE="Tests/Playwright/modules/PublishOverview/publish-changed-content.spec.ts"

# Open Playwright UI for interactive test development (http://localhost:9323)
make playwright-ui

# View the last test report (http://localhost:9323)
make playwright-report
```

### From the monorepo root

```bash
# Run all in2publish_core tests (uninstalls enterprise extension, restores DB, clears caches)
make playwright

# Run specific test file
make playwright FILE="Tests/Playwright/modules/PublishOverview/publish-changed-content.spec.ts"
```

**Important:** When running from the monorepo, `make playwright` automatically uninstalls the enterprise extension (`in2code/in2publish`) before running tests. This ensures that `in2publish_core` tests run in isolation without enterprise features. Use `make install-enterprise` afterwards to restore it for normal development or enterprise testing.

## Architecture

### Test Structure
```
Tests/Playwright/
├── config.ts                   # Test configuration (local/foreign URLs)
├── global.setup.ts             # Global setup (DB restore + authentication)
├── fixtures/                   # Custom fixtures
│   ├── backend-page.ts         # TYPO3 backend page fixture
│   └── setup-fixtures.ts       # Setup fixtures
├── helpers/                    # Test helpers
│   ├── direct-restore.ts       # Direct database + fileadmin restore (used in CI/Docker)
│   └── Environment.ts          # Environment reset via `make restore` (used locally)
└── modules/                    # Test files organized by module
    ├── PublishOverview/
    ├── PublishFiles/
    ├── RedirectsModule/
    └── Regression/
```

### Configuration

The test configuration is in `playwright.config.ts` at the project root.

**Key Configuration:**
- Base URL: `https://local.v13.in2publish-core.de/typo3/` (override with `PLAYWRIGHT_BASE_URL`)
- Workers: 1 (sequential execution due to shared database state)
- Retries: 2 on CI, 0 locally
- Timeout: 60 seconds per test

### Database Restore

Tests rely on a clean, known database state restored from dumps in `.project/data/dumps/`.

**Two restore mechanisms exist:**

1. **`direct-restore.ts`** (used in Docker/CI via `global.setup.ts`): Connects directly to MySQL and re-imports dump data using `LOAD DATA INFILE`. This is the primary mechanism and runs automatically at the start of each test suite. Individual tests that modify data use `restoreDatabases()` in `test.beforeEach()` for per-test isolation.

2. **`Environment.reset()`** (used locally, skipped in CI): Runs `make restore` from the monorepo root via shell. Used in `test.beforeAll()` blocks for local development.

**Dump architecture:** The dumps in `in2publish_core/.project/data/dumps/` include tables from both `in2publish_core` and the enterprise extension (`in2publish`). Enterprise tables (e.g. `tx_in2publish_workflow_state`, `tx_in2publish_workflow_history`) are always present in the dumps but do not interfere with core-only tests. The enterprise package maintains its own supplementary dump files for workflow-specific test data.

### Environment Isolation

The `in2publish_core` tests operate in an isolated environment:
- **Local System**: `https://local.v13.in2publish-core.de`
- **Foreign System**: `https://foreign.v13.in2publish-core.de`

When running from the monorepo, the enterprise extension is uninstalled to ensure tests validate core functionality in isolation.

### Authentication

Tests use authenticated sessions stored in `Tests/Playwright/.auth/login.json`. The authentication is performed once in `global.setup.ts` and reused across all tests for performance.

## Docker Implementation Details

### How It Works

The Docker setup uses a dedicated Playwright service in docker-compose:

1. Playwright runs in Microsoft's official Playwright Docker image
2. The container joins your existing Docker network
3. Tests access your already-running TYPO3 instances (local + foreign) through the network
4. Test files and results are mounted via volume at `/work`
5. The service runs continuously (`sleep infinity`) for fast test execution

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `PLAYWRIGHT_BASE_URL` | Base URL for local TYPO3 instance | `https://local.v13.in2publish-core.de/typo3/` |
| `PLAYWRIGHT_FOREIGN_BASE_URL` | Base URL for foreign TYPO3 instance | `https://foreign.v13.in2publish-core.de/typo3/` |
| `CI` | Enable CI mode (retries, forbidOnly) | `0` (set to `1` in CI) |

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

**Tests fail due to database state:**
```bash
make restore
```

## Maintenance

### Updating Playwright

Update the image version in `.project/docker/docker-compose.*.yaml` (or `docker-compose.yml` in monorepo):
```yaml
playwright:
  image: mcr.microsoft.com/playwright:v1.XX.X-noble
```

Then recreate the container:
```bash
docker compose up -d playwright
```

Also update `@playwright/test` in `package.json` to match the Docker image version.

### Updating Dumps

After making changes to the database that should be reflected in test data:
```bash
make dump-dbs
```

This exports both local and foreign databases to `.project/data/dumps/`.

## Resources

- [Playwright Documentation](https://playwright.dev/)
- [TYPO3 Testing Documentation](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Testing/)
- [Microsoft Playwright Docker Images](https://mcr.microsoft.com/en-us/product/playwright/about)
