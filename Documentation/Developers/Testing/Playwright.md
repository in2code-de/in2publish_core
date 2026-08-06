# Playwright testing

The Playwright suites test publishing between two real TYPO3 installations in a browser. Both
`in2publish_core` and `in2publish` (Enterprise) have their own isolated Docker Compose stack, their
own databases and their own host names. They do not use the development stack at the repository
root.

## Quick start

Provision each extension-local TYPO3 environment once before its first test run:

```bash
cd packages/in2publish_core
make setup

cd ../in2publish
make setup
```

Tests can then be started from the repository root:

```bash
# Run both suites, one after the other
make test-playwright

# Run one suite
make test-playwright-core
make test-playwright-enterprise

# Run one file in a suite
make test-playwright-core FILE="Tests/Playwright/modules/01-PublishOverview/publish-changed-content.spec.ts"

# Open the Playwright UI or the latest report
make playwright-core-ui
make playwright-core-report
make playwright-enterprise-ui
make playwright-enterprise-report

# Stop running Playwright tasks
make playwright-stop
```

The corresponding commands inside an extension directory are `make test-playwright`,
`make playwright-ui`, `make playwright-report` and `make playwright-stop`.

## Test stacks

Each stack represents the normal Content Publisher setup:

```text
local TYPO3  ---- publishing ---->  foreign TYPO3
```

The following long-running services are started for a test suite:

| Service | Purpose |
|---|---|
| `local` | HTTP server for the local TYPO3 installation |
| `local-php` | PHP runtime for the local TYPO3 installation |
| `foreign` | HTTP server for the foreign TYPO3 installation |
| `foreign-php` | PHP runtime for the foreign TYPO3 installation |
| `mysql` | Holds the separate `local` and `foreign` databases |
| `mail` | Captures test email |

The Enterprise stack additionally starts `local-solr` and `foreign-solr`.

TYPO3 is stored on the host below the extension directory, not inside an anonymous container
volume:

```text
Build/local/       local TYPO3 installation
Build/foreign/     foreign TYPO3 installation
```

The HTTP services serve `Build/local/public` and `Build/foreign/public`; the PHP services execute
the corresponding TYPO3 installations. The default backend URLs are:

| Suite | Local | Foreign |
|---|---|---|
| Core | `https://local.v13.in2publish-core.de/typo3/` | `https://foreign.v13.in2publish-core.de/typo3/` |
| Enterprise | `https://local.v13.in2publish-enterprise.de/typo3/` | `https://foreign.v13.in2publish-enterprise.de/typo3/` |

The Playwright service is different from the services above. `docker compose run --rm playwright`
creates a temporary container for the test command. Chromium, Node.js and the test runner execute
inside it, so Node.js and browser binaries are not required on the host. The container joins the
same Docker network as TYPO3 and is removed after the command finishes.

## Provisioning and startup

`make setup` is the one-time, comparatively expensive provisioning step. It recreates the stack,
installs Composer dependencies, runs `typo3 install:setup` for both TYPO3 installations and restores
their initial data and files.

A normal Playwright run does not reinstall TYPO3. It:

1. acquires the extension-local `.playwright.lock`, preventing two commands from changing the same
   test environment concurrently;
2. selects the platform-specific Compose file and verifies that `Build/*/vendor` exists;
3. builds the Playwright image and starts the extension-local services;
4. creates the temporary Playwright container, runs `npm install`, and starts `npx playwright test`.

Core and Enterprise use different Compose project names, ports, host names and lock files. Their
suites may therefore exist independently even though `make test-playwright` deliberately runs them
one after another.

## Restore lifecycle

The database dumps and fileadmin fixtures are snapshots of a known test state. Restoring them makes
tests independent of changes made by earlier tests.

### Once per test run: `playwright-prepare`

The Playwright setup project calls `make playwright-prepare` before the browser tests start. It:

1. restores both databases;
2. restores local and foreign `fileadmin`;
3. creates and empties tables that must exist but are intentionally absent from the foreign dump;
4. applies pending TYPO3 database schema changes;
5. clears TYPO3 page caches.

Core also authenticates once during global setup and stores the browser session in
`Tests/Playwright/.auth/login.json`. Enterprise normally logs in through its test fixture.

### Before each test: `playwright-reset`

The automatic fixture calls `make playwright-reset` before each test. This faster reset:

- imports the local and foreign database snapshots again;
- recreates the required empty foreign tables;
- clears volatile database state such as caches, sessions, record locks and messenger messages.

It deliberately does not restore fileadmin, update the schema or flush TYPO3 caches through the
TYPO3 command line. Those expensive operations have already happened during `playwright-prepare`.

Tests run sequentially (`workers: 1`) because they share the same TYPO3 installations and database
state.

### Tests that change files: `playwright-reset-files`

A test that uploads, renames, moves, deletes or publishes files must also restore fileadmin. It must
disable the normal automatic reset and request the file reset instead:

```typescript
test.use({ autoRestore: false });

test.beforeEach(async ({ page }) => {
  await resetEnvironment(page, 'playwright-reset-files');
});
```

`playwright-reset-files` performs the database/runtime reset and restores fileadmin. Disabling the
automatic reset avoids importing the databases twice for the same test.

## Core and Enterprise fixture data

Core restores its own database dumps and fileadmin snapshots directly.

Enterprise is an extension of Core, so its baseline is layered:

```text
Core fixture data  ->  Enterprise fixture overlay  ->  ready Enterprise test state
```

For databases, the Core dumps supplied by the Composer-installed `in2publish_core` package are
imported first, followed by the Enterprise-specific dumps. Fileadmin follows the same rule: Core
files are restored as the base and Enterprise files are copied over them. This avoids duplicating
the complete Core fixture set in the Enterprise repository.

## Configuration and debugging

The relevant files are:

- `playwright.config.ts`: browser projects, timeouts, reporters and worker count;
- `.env`: host names, SQL port and Playwright UI port;
- `.project/docker/docker-compose.*.yaml`: TYPO3 and Playwright services;
- `Tests/Playwright/global.setup.ts`: once-per-run preparation;
- `Tests/Playwright/fixtures/setup-fixtures.ts`: per-test restore and login behavior.

Traces, screenshots and videos are retained for failed tests. Use `make playwright-report` in the
extension directory, or the matching root wrapper, to inspect the latest HTML report. In UI mode,
select the `chromium` project to see the browser tests.

Individual tests may opt out of automatic restore or login through fixture options when their setup
requires it. Such tests are responsible for establishing a clean state themselves.

## Further reading

- [Playwright documentation](https://playwright.dev/docs/intro)
- [TYPO3 testing documentation](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Testing/)
