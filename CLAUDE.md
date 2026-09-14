# Project: in2publish_core (Content Publisher Community Edition)

Content publishing extension to connect stage (Local) and production (Foreign) TYPO3 servers.

## TYPO3 Configuration

**TYPO3 Version**: ^14.2
**PHP Version**: `>= 8.2.0 <= 8.4.99`
**Extension Version**: 14.0.5 (ext_emconf), state `stable`
**Extension Key**: in2publish_core
**Vendor Namespace**: In2code\In2publishCore
**Project Type**: Docker Compose + Composer Mode (own extension-local stack)

### Project Architecture

This extension connects a **Local** (staging) TYPO3 instance with a **Foreign** (production) instance for content
publishing. It is developed inside the `in2publish-dev-t3v14-cpv14` dev environment (mounted at `/packages/`) but
carries its own self-contained Docker stack and TYPO3 instances for tests.

Key concepts:
- **Local**: The `Build/local` TYPO3 instance where editors work
- **Foreign**: The `Build/foreign` TYPO3 instance that receives published content
- Two separate database connections (`$localDatabase`, `$foreignDatabase`) injected via DI
- Adapter pattern for SSH/HTTP/Native communication between instances

### Extension Class Structure

```
Classes/
├── Backend/          # Backend module related
├── Cache/            # Cache implementations
├── Command/          # CLI commands (Local/ and Foreign/ subfolders)
├── CommonInjection/  # Shared DI traits
├── Component/        # Core components (modular architecture)
├── Controller/       # Extbase controllers
├── Event/            # PSR-14 events
├── Factory/          # Factory classes (ConnectionFactory)
├── Features/         # Feature modules (modular architecture)
├── Listener/         # Event listeners
├── Log/              # Logging
├── Middleware/        # PSR-15 middlewares
├── Service/          # Service classes
├── Testing/          # In-app testing framework
├── Utility/          # Utility classes
└── ViewHelpers/      # Fluid ViewHelpers
```

### Configuration Structure

```
Configuration/
├── Backend/          # Backend module config
├── Component/        # Component-specific Services.yaml
├── Features/         # Feature-specific Services.yaml
├── TCA/Overrides/    # TCA overrides
├── Yaml/             # YAML config files
├── Services.yaml     # Main DI configuration
├── Services.php      # PHP-based service config
├── Icons.php         # Icon registration
├── JavaScriptModules.php
├── RequestMiddlewares.php
├── ForeignServices.php   # Services for Foreign context
└── LocalServices.php     # Services for Local context
```

### Dependency Injection

- Autowiring enabled with autoconfigure
- Two named database connections via factory:
  - `$localDatabase` → `ConnectionFactory::createLocalConnection()`
  - `$foreignDatabase` → `ConnectionFactory::createForeignConnection()`
- Cache: `cache.in2publish_core`
- Modular Services.yaml imports from `Component/*/Services.yaml` and `Features/*/Services.yaml`


### Docker Environment

- Docker Compose config: `.project/docker/docker-compose.darwin.yaml` (symlinked; `.yaml`, not `.yml`)
- **Services**: local (httpd), local-php, foreign (httpd), foreign-php, mysql, mail (Mailpit), playwright
- **Dual TYPO3 instances**: Local and Foreign running side-by-side under `Build/local` and `Build/foreign`
- `IN2PUBLISH_CONTEXT` env var distinguishes Local/Foreign
- `make setup` provisions the whole stack from scratch; targets that need provisioned instances fail early via
  `.ensure-provisioned`

### Development Tools

- **PHPUnit**: Runs inside the `local-php` container against `phpunit.unit.xml` / `phpunit.functional.xml`
  (`make test-unit` / `make test-functional`). Also reachable from the dev-environment root.
- **Playwright**: Browser tests in `Tests/Playwright/`. Runs in this extension's own Docker stack.
  Invoke it from this directory (`make test-playwright`, `make playwright-ui`, `make playwright-report`,
  `make playwright-stop`) or via the dev-environment root wrappers (`make test-playwright-core`,
  `make playwright-core-ui`, `make playwright-core-report`). All of them accept `FILE=<path>` to run a single spec.
  Core uses a package-local `.playwright.lock` and restores **its own** fixtures from
  `.project/data/dumps/{local,foreign}` and `.project/data/fileadmin/`. Restore is driven through the extension
  Makefile (`make restore` / `make restore-db`, also callable from the Playwright container), which guarantees the
  known empty tables exist on `foreign` (`FOREIGN_ONLY_EMPTY_TABLES` / `.ensure-foreign-empty-tables`).
  The Playwright image is built from `.project/docker/playwright/Dockerfile`
  (`FROM mcr.microsoft.com/playwright:v1.59.1-noble`); `@playwright/test` in `package.json` must match that tag.
  UI mode port: `PLAYWRIGHT_UI_PORT` in `.env` (default `9424`).
- **QA**: php-cs-fixer, PHP_CodeSniffer and PHPMD run as phars via phive in a throwaway `in2code/php:8.3-fpm`
  container — `make qa-setup` once, then `make qa` (or the individual `qa-php-*` / `fix-php-*` targets).
  Configs live in `.project/qa/`. No PHPStan or Rector.
- **CSS**: Edit `Resources/Public/Css/*.css` directly; no frontend build step is required.

### Test Structure

```
Tests/
├── Functional/    # Functional tests
├── Manual/        # Manual test procedures
├── Playwright/    # Playwright E2E tests (incl. shared/ helpers re-used by the enterprise suite)
├── Unit/          # Unit tests
├── FunctionalTestCase.php
└── UnitTestCase.php
```

### Makefile Commands

Key targets available in this extension's directory (`make help` lists the annotated ones):
- `make setup` - Full provisioning of the extension-local stack (destroy, hosts, certs, start, install)
- `make start` / `make stop` / `make destroy` - Docker lifecycle for the extension-local test/dev stack
- `make composer-install` / `make composer-update` - Composer operations
- `make restore` - `restore-db` + `fileadmin-restore` + page cache flush, reading this extension's own
  `.project/data/dumps/` and `.project/data/fileadmin/`. `restore-db` also recreates the foreign-only empty tables
  from `FOREIGN_ONLY_EMPTY_TABLES`.
- `make typo3-comparedb` / `make typo3-clearcache` / `make typo3-clear-pagecache` / `make typo3-rebuild-caches`
- `make login-local-php` / `make login-foreign-php` - Shell access
- `make hosts` / `make urls` - Hosts entry and project URLs
- `make qa-setup` then `make qa` - QA tooling
- `make test-unit` / `make test-functional` - PHPUnit suites (also runnable from the dev-environment root)

Playwright targets in this extension:
- `make test-playwright` / `make playwright-ui` / `make playwright-report` / `make playwright-stop`
- Fixture helpers: `make playwright-prepare`, `make playwright-reset`, `make playwright-reset-files`
- The dev-environment root wrappers delegate to these same targets:
  `make test-playwright-core` / `make playwright-core-ui` / `make playwright-core-report`

### TYPO3 v14 Guidelines

#### 1. Controllers & Actions
- All controller actions MUST return `Psr\Http\Message\ResponseInterface`
- Use `$this->htmlResponse()`, `$this->jsonResponse()`, or `$this->redirectToUri()`

#### 2. Dependency Injection
- Constructor-based DI only
- Use `#[Autowire]` attribute when needed
- Configure in Services.yaml (autowire + autoconfigure enabled)

#### 3. TCA Configuration
- Use modern TCA types: `number`, `datetime`, `email`, `color`, `slug`, `category`, `folder`, `file`
- Removed deprecated `eval` options

#### 4. Events & Hooks
- PSR-14 Events only (legacy hooks removed)
- Register events in `Configuration/Services.yaml`

#### 5. Request Handling
- Use request attributes instead of `$GLOBALS['TSFE']`
- Access PageArguments via request: `$request->getAttribute('routing')`

#### 6. Database Queries
- QueryBuilder with named parameters only
- Use `createNamedParameter()` for all variables

#### 7. Strict Types & Type Declarations
- Always use `declare(strict_types=1);`
- Use proper type hints for all parameters and return types

#### 8. Configuration Files
- Use `defined('TYPO3') || die();` in config files

# Development Guidelines

## Working Mode
* **File Management:** You may read, create, and modify files without prior consultation.
* **Destructive Actions:** Always ask for confirmation before performing destructive actions (e.g., deletions or database migrations without a rollback path).
* **Testing:** Writing and executing tests is always permitted and encouraged.
* **Handling Ambiguity:** If a requirement is unclear, make a reasonable assumption, document it, and proceed.
* **Refactoring:** No prior approval is needed for refactoring within a specific module.
* **Code Style:** Follow existing code style and conventions. No prior approval needed for code style fixes.
* **Documentation:** You may update or add documentation (e.g., README, inline comments) without prior approval.
* **Git Commits:** You may commit changes with clear messages following guidelines above, but you are never allowed to push changes.

## Restrictions (Require Explicit Approval)
* **Dependencies:** Modifying `composer.json` or adding/removing dependencies.
* **Database Schema:** Deploying any changes to the database schema.
* **External APIs:** Calling live external APIs (permitted only within tests using mocks/stubs).
* **Git Pushes:** You are not allowed to push changes to the repository.

### Git Information

**Commit Message Convention:**
- `[BUGFIX]` - Bug fixes
- `[TASK]` - General tasks
- `[FEATURE]` - New features
- `[DOCS]` - Documentation changes
- `[CODESTYLE]` - Code style fixes
- `[TEST]` - Test additions/changes
- `[SECURITY]` - Security updates
- `[DEV]` - Changes related to development environment or tools
- `[AI]` - Instructions / plans for AI tools

* Keep messages concise, focussed on the change, and avoid unnecessary details/explanations.
* Use the present tense ("Fix bug" not "Fixed bug").
* Do not add statements like authored by Claude-Code or AI assisted in commit message.
