# Playwright Testing for in2publish_core

This document covers end-to-end browser tests for both the community edition (`in2publish_core`)
and the enterprise edition (`in2publish`).

## Quick Start

**All Playwright tests are launched from the monorepo root.** The previous per-extension Docker
stacks were removed because they interfered with each other. Both test suites now run inside the
main project's `playwright` / `playwright-enterprise` service containers and share the same
local/foreign TYPO3 instances, database, and fileadmin.

Because the shared state cannot be used by both suites simultaneously, a `.playwright.lock`
directory at the monorepo root enforces a strict **single-suite-at-a-time** rule. You can run
core OR enterprise, but never both in parallel.

### Prerequisites

1. The main project Docker stack must be running (`make start` from the monorepo root).
2. Playwright npm dependencies must be installed once after cloning or after updates:

```bash
# Install dependencies for both suites
make setup-tests

# Or only one suite
make setup-tests-core
make setup-tests-enterprise
```

### Running all tests (from monorepo root)

```bash
# Run all core tests
make playwright-core

# Run all enterprise tests
make playwright-enterprise

# Run both suites sequentially under a single lock
make playwright
```

Each `playwright-core` / `playwright-enterprise` run automatically:

1. Acquires the `.playwright.lock`.
2. Installs or removes the `in2code/in2publish` meta-package (core runs must remove enterprise;
   enterprise runs must install it).
3. Clears the TYPO3 DI code cache on both instances.
4. Restores DB and fileadmin from `.project/data/dumps/` and `.project/data/fileadmin/`.
5. Clears TYPO3 caches.
6. Runs the suite in the appropriate container and writes an HTML report to
   `.reports/playwright/<suite>/html`.

### Running a single test file (from monorepo root)

```bash
# Core: run one spec file
make playwright-core FILE="Tests/Playwright/modules/01-PublishOverview/publish-changed-content.spec.ts"

# Enterprise: run one spec file
make playwright-enterprise FILE="Tests/Playwright/tests/02-Workflow/page-workflow-publishing.spec.ts"
```

`FILE` is a path relative to the extension's `packages/<ext>/` directory.

### Playwright UI mode (debugging only)

UI mode is useful for authoring and debugging individual tests.
**Do not use it for regular test runs:** Playwright's own WebSocket connection prevents
`networkidle` from stabilising, making tests unreliable in UI mode.

```bash
make playwright-core-ui                     # http://localhost:9426 (PLAYWRIGHT_CORE_UI_PORT)
make playwright-enterprise-ui               # http://localhost:9427 (PLAYWRIGHT_ENTERPRISE_UI_PORT)

# Filter to a single file
make playwright-core-ui FILE="Tests/Playwright/modules/01-PublishOverview/publish-changed-content.spec.ts"
make playwright-enterprise-ui FILE="Tests/Playwright/tests/02-Workflow/page-workflow-publishing.spec.ts"
```

UI ports are configurable via `.env`.

In UI mode, make sure the **"chromium"** project is checked in the project filter at the top.

### Viewing the last test report

```bash
make playwright-core-report        # http://localhost:9426
make playwright-enterprise-report  # http://localhost:9427
```

---

## Architecture

### Test locations

```
packages/
├── in2publish_core/Tests/Playwright/
│   ├── shared/                         # Shared code loaded by both extensions
│   │   ├── fixtures/
│   │   │   ├── backend-page.ts         # Base BackendPage fixture
│   │   │   └── setup-fixtures.ts       # Base test setup
│   │   ├── helpers/
│   │   │   ├── direct-restore.ts       # Database + fileadmin restore logic
│   │   │   ├── backend-login.helper.ts
│   │   │   ├── config.ts
│   │   │   ├── environment.helper.ts
│   │   │   └── typo3.helper.ts
│   │   └── setup/
│   │       └── global-login-setup.ts
│   ├── fixtures/                        # Core-specific fixtures
│   │   ├── backend-page.ts             # Core BackendPage (extends shared)
│   │   └── setup-fixtures.ts
│   ├── helpers/
│   │   └── direct-restore.ts           # Re-exports from shared
│   ├── modules/                         # Core test files (by module)
│   │   ├── 00-PublisherTools/
│   │   ├── 01-PublishOverview/
│   │   ├── 02-PublishFiles/
│   │   ├── 03-RedirectsModule/
│   │   └── 04-Miscellaneous/
│   ├── config.ts
│   └── global.setup.ts
│
└── in2publish/Tests/Playwright/
    ├── fixtures/
    │   ├── backend-page.ts             # Enterprise BackendPage (extends core BackendPage)
    │   └── setup-fixtures.ts           # Re-exports from shared
    ├── helpers/
    │   └── direct-restore.ts           # Re-exports from shared
    ├── tests/                           # Enterprise test files (by feature)
    │   ├── 00-PublisherTools/
    │   ├── 01-PublishNewPage/
    │   ├── 02-Workflow/
    │   ├── 03-LanguageControl/
    │   ├── 04-SingleRecordPublishing/
    │   ├── 05-ScheduledPublishing/
    │   └── 06-Miscellaneous/
    ├── config.ts
    └── global.setup.ts
```

### Shared code in in2publish_core

All reusable Playwright infrastructure lives in `in2publish_core/Tests/Playwright/shared/` and is
imported by both extensions. The enterprise extension (`in2publish`) never duplicates shared code —
it imports directly from the relative path into `in2publish_core`.

**Base `BackendPage`** (`shared/fixtures/backend-page.ts`):
Provides `login()`, `gotoModule()`, `searchInPageTreeAndSelectFirstOccurrence()`, and
`contentFrame`. Both extensions extend this class.

**Enterprise `BackendPage`** (`in2publish/Tests/Playwright/fixtures/backend-page.ts`):
Extends the base with workflow helpers: `waitUntilPublishingFinished()`, `clickWorkflowPublishButton()`,
`setWorkflowState()`, `searchAndWaitForWorkflowRecords()`, `assertNodeHasStatus()`, etc.

**`direct-restore.ts`** (`shared/helpers/direct-restore.ts`):
Contains the actual restore logic. Both `in2publish_core/helpers/direct-restore.ts` and
`in2publish/helpers/direct-restore.ts` simply re-export from it.

### Database / fileadmin restore

Tests rely on a known, clean database state. The monorepo's `.project/data/dumps/` and
`.project/data/fileadmin/` are the single source of truth and are bind-mounted into the main
project's `local-php`, `foreign-php`, and `playwright*` service containers. There are two layers
of restore:

**1. Suite-level restore (automatic)**
The `make playwright-core` / `make playwright-enterprise` targets call `make restore` before each
run, which executes `mysql-loader import` for both databases and `rsync` for the fileadmin.

**2. Per-test restore (in `test.beforeEach`)**
Tests that modify data (publish actions, workflow state changes) call `restoreDatabases()` in
`test.beforeEach()` to reset before each test case.

```typescript
import { restoreDatabases } from '../../helpers/direct-restore';

test.beforeEach(async () => {
    await restoreDatabases();
});
```

**How the per-test restore works:**
- Connects directly to MySQL from inside the Playwright Docker container
- Reads dump files from `.project/data/dumps/{local,foreign}/`
- Truncates each table and reloads it via `LOAD DATA INFILE` from CSV
- Cache tables are truncated but not reloaded
- The path is resolved from the `DUMPS_DIR` env var that is set on the `playwright*` services

**Updating dumps** after a database change that should persist in test data:

```bash
# From the monorepo root
make dump-dbs
```

Dumps can also be refreshed from either extension directory — both invocations read from and
write to the same monorepo-level `.project/data/dumps/`.

### Authentication

Tests share a single authenticated session stored in `Tests/Playwright/.auth/login.json`.
`global.setup.ts` performs login once and all tests reuse the stored session.

---

## Writing Tests

See the [Playwright documentation](https://playwright.dev/docs/writing-tests) for general guidance.

### Core test example

```typescript
import { test, expect } from '../../fixtures/setup-fixtures';

test.describe('Publish Overview Module', () => {
  test('should display changed records', async ({ backend }) => {
    await backend.login();
    await backend.gotoModule('Publish Overview');

    await expect(
      backend.contentFrame.locator('.record-list')
    ).toBeVisible();
  });
});
```

### Enterprise test example

```typescript
import { test, expect } from '../../fixtures/setup-fixtures';
import { restoreDatabases } from '../../helpers/direct-restore';

test.describe('Workflow Publishing', () => {
  test.beforeEach(async () => {
    await restoreDatabases();
  });

  test('should publish a page through workflow', async ({ backend }) => {
    test.setTimeout(180000);

    await backend.login();
    await backend.gotoModule('Publish Workflow');
    await backend.searchInPageTreeAndSelectFirstOccurrence('My Page');
    await backend.clickWorkflowPublishButton();
    await backend.waitUntilPublishingFinished();

    await expect(backend.contentFrame.locator('body')).toContainText('Successfully published');
  });
});
```

---

## Troubleshooting

**"Another Playwright make task is active":**
The `.playwright.lock` directory is held by another `make playwright-*` invocation. Wait for it to
finish, or — if you are sure nothing else is running — remove the lock manually:

```bash
rm -rf .playwright.lock
```

**Tests not visible in UI mode:**
Make sure the **"chromium"** project is checked in the project filter at the top. The UI
remembers your selection in browser localStorage.

**Tests time out only in UI mode:**
This is a known Playwright limitation. Playwright's own inspector keeps a WebSocket connection
open which prevents `networkidle` from resolving in reasonable time. Use headless mode
(`make playwright-core` / `make playwright-enterprise`) for regular runs. UI mode is for debugging
individual tests only.

**Tests fail due to stale database state:**

```bash
# Manual restore from the monorepo root
make restore
```

The same target also works from inside an extension directory; both invocations use the monorepo
dumps.

**`LOAD DATA INFILE` permission error:**
The MySQL container must be started with `--local-infile=1` / `--secure-file-priv=$DUMPS_DIR`
(already configured in the dev stack). If you see permission errors, recreate the MySQL container.

---

## Maintenance

### Updating Playwright version

Update the image version in the main project's `.project/docker/docker-compose.darwin.yml` for
both services:

```yaml
playwright:
  image: mcr.microsoft.com/playwright:v1.XX.X-noble
playwright-enterprise:
  image: mcr.microsoft.com/playwright:v1.XX.X-noble
```

Also update `@playwright/test` in each extension's `package.json` to match exactly. A mismatch
between the package version and the Docker image version causes test runner errors.

Recreate the containers after updating:

```bash
docker compose up -d playwright playwright-enterprise
```

### Resources

- [Playwright Documentation](https://playwright.dev/)
- [Microsoft Playwright Docker Images](https://mcr.microsoft.com/en-us/product/playwright/about)