# in2publish_core — Claude Code Configuration

This file provides project-specific guidance for the **in2publish Core** extension.
For the overall development environment, see the root-level `CLAUDE.md` at the project root.

## TYPO3 Configuration

**Extension**: in2publish Core (`in2publish_core`)
**Version**: 12.8.0 (stable)
**TYPO3 Compatibility**: ^12.4
**PHP Requirement**: ^8.0
**Vendor/Namespace**: `In2code\In2publishCore`
**Extension Key**: `in2publish_core`
**Composer Package**: `in2code/in2publish_core`
**Current Branch**: `develop-v12`

## Project Purpose

in2publish Core is a **content publishing extension** that connects a TYPO3 staging server (local) to a production server (foreign). It synchronizes content, files, and records between the two TYPO3 instances via SSH/RPC. This is a **dual-instance** setup where both Local and Foreign TYPO3 run separately, sharing a MySQL server but with distinct databases.

## Architecture Overview

### Dual-Context Design
The extension operates in two distinct contexts:
- **Local context** (`IN2PUBLISH_CONTEXT=local`): The staging instance where editors work
- **Foreign context** (`IN2PUBLISH_CONTEXT=foreign`): The production instance that receives published content

Code that only runs on one side lives in:
- `Classes/Command/Local/` — CLI commands for local only
- `Classes/Command/Foreign/` — CLI commands for foreign only
- `Configuration/LocalServices.php` — DI container services for local
- `Configuration/ForeignServices.php` — DI container services for foreign

### Extension Structure

```
Classes/
├── Backend/         # Backend UI helpers, toolbar items
├── Cache/           # Cache-related classes
├── Command/         # Symfony Console commands (Local/ and Foreign/ subfolders)
├── CommonInjection/ # Shared DI traits/interfaces
├── Component/       # Reusable components (RPC, ConfigContainer, etc.)
├── Controller/      # Extbase controllers (Backend modules)
├── Event/           # PSR-14 Event classes
├── Factory/         # Factory classes (e.g., ConnectionFactory for dual databases)
├── Features/        # Optional feature classes (enabled via config)
├── Listener/        # PSR-14 EventListeners
├── Log/             # Logging classes
├── Middleware/       # PSR-15 Middlewares
├── Service/         # Service classes
├── Testing/         # Test helpers/base classes
├── Utility/         # Utility classes
└── ViewHelpers/     # Fluid ViewHelpers

Configuration/
├── Backend/         # Backend module registration
├── Component/       # Per-component Services.yaml files
│   ├── Core/
│   ├── RemoteProcedureCall/
│   ├── PostPublishTaskExecution/
│   ├── TemporaryAssetTransmission/
│   ├── ConfigContainer/
│   ├── Shared/
│   └── RemoteCommandExecution/
├── Features/        # Per-feature Services.yaml files (23 features)
├── TCA/Overrides/   # TCA overrides (sys_redirect.php)
├── Yaml/            # in2publish YAML configuration schemas
├── Services.yaml    # Main DI configuration (imports Component/*/Services.yaml and Features/*/Services.yaml)
├── LocalServices.php
├── ForeignServices.php
├── Icons.php
├── JavaScriptModules.php
├── RequestMiddlewares.php
└── Services.php

Tests/
├── Browser/         # Browser/acceptance tests
├── Functional/      # Functional tests (FunctionalTestCase.php)
├── Unit/            # Unit tests (UnitTestCase.php)
├── Helper/          # Test helpers
└── Manual/          # Manual test scripts
```

### Features System
The extension has a modular **features system** — optional capabilities enabled via YAML config. Each feature lives in:
- `Classes/Features/<FeatureName>/` — PHP classes
- `Configuration/Features/<FeatureName>/Services.yaml` — DI config

Known features:
`AdminTools`, `CacheInvalidation`, `CompareDatabaseTool`, `ConditionalEventListener`,
`ContextMenuPublishEntry`, `FileEdgeCacheInvalidator`, `FullTablePublishing`,
`HideRecordsDeletedDifferently`, `LogPublishingEvents`, `MetricsAndDebug`,
`NewsSupport`, `PreventParallelPublishing`, `PublishSorting`, `RecordBreadcrumbs`,
`RecordInspector`, `RedirectsSupport`, `RefIndexUpdate`, `ResolveFilesForIndices`,
`SysLogPublisher`, `SystemInformationExport`, `WarningOnForeign`

### Dependency Injection
`Configuration/Services.yaml` uses:
- `autowire: true` and `autoconfigure: true` as defaults
- Named service bindings: `$localDatabase` and `$foreignDatabase` (both `TYPO3\CMS\Core\Database\Connection`)
- `$cache` bound to `cache.in2publish_core`
- Factory pattern for dual database connections via `ConnectionFactory`
- Imports from `Component/*/Services.yaml` and `Features/*/Services.yaml`

## Development Environment

### Docker Services (in-package docker-compose)
The extension has its own Docker setup at `.project/docker/`:
```
docker-compose.darwin.yml    # macOS variant
docker-compose.linux.yml     # Linux variant
docker-compose.ci.yml        # CI/CD variant
```
Symlink: `docker-compose.yaml` → `.project/docker/docker-compose.darwin.yml`

Containers:
- `local-php` — Local TYPO3 PHP container
- `foreign-php` — Foreign TYPO3 PHP container
- `mysql` — Shared MySQL 8.0

### Package Makefile Commands
Run from `packages/in2publish_core/`:

| Command | Description |
|---------|-------------|
| `make setup` | Full setup: destroy, install, start, restore DB |
| `make start` | Start Docker containers |
| `make stop` | Stop containers |
| `make destroy` | Destroy containers and volumes |
| `make restore` | Restore test databases |
| `make unit` | Run unit tests |
| `make functional` | Run functional tests |
| `make acceptance` | Run acceptance/browser tests |
| `make setup-qa` | Install QA tools via phive |
| `make qa` | Run all QA checks |
| `make qa-php-cs-fixer` | Run PHP CS Fixer (check only) |
| `make fix-php-cs-fixer` | Run PHP CS Fixer (fix mode) |
| `make qa-php-code-sniffer` | Run PHP_CodeSniffer |
| `make fix-php-code-sniffer` | Run PHP Code Beautifier |
| `make qa-php-mess-detector` | Run PHPMD |
| `make typo3-clearcache` | Clear TYPO3 caches |
| `make typo3-rebuild-caches` | Hard-delete and rebuild DI cache |
| `make composer-install` | Install composer deps in both containers |
| `make composer-update` | Update composer deps in both containers |
| `make login-local-php` | Shell into local-php container |
| `make login-foreign-php` | Shell into foreign-php container |

### QA Tools (via phive, not composer)
Tools are installed via `phive` to `.project/phars/`:
- **grumphp** v1.5.0 — Pre-commit hook runner
- **phpcs** v3.8.1 (PHP_CodeSniffer)
- **phpcbf** v3.8.1 (PHP Code Beautifier)
- **php-cs-fixer** v3.48.0
- **phpmd** v2.15.0 (PHP Mess Detector)

**No PHPStan or Rector** configured in this package.

### Frontend Build
- Located in: `Resources/Private/Build/`
- Build tool: **Gulp 4** (`gulpfile.js`)
- CSS: **Sass/SCSS** via `gulp-sass`
- Run: `make build-frontend` (from project root) or `npm run build` / `gulp` in the Build directory

### Test Configuration
- `phpunit.unit.xml` — Unit test config
- `phpunit.functional.xml` — Functional test config
- `phpunit.browser.xml` — Browser/acceptance test config
- Base classes: `Tests/UnitTestCase.php`, `Tests/FunctionalTestCase.php`
- Test types: Unit, Functional, Browser (acceptance)

## Sites (Local Instance)

- **main** — `https://local.v12.in2publish.de/` (English, German, Japanese)
- **styleguide_frontend_demo** — `/styleguide_frontend_demo`
- **styleguide_tca_demo** — `/styleguide_tca_demo`

Foreign instance URL: `https://foreign.v12.in2publish.de/`
Backend credentials: `admin` / `password`

## TYPO3 v12 Coding Guidelines for This Project

### 1. Strict Types and Namespace
```php
<?php
declare(strict_types=1);
namespace In2code\In2publishCore\<Subfolder>;
```

### 2. Dependency Injection
- Constructor injection **only** (no `GeneralUtility::makeInstance` for services)
- All services are registered in `Services.yaml` (autowired by default)
- Use named parameters `$localDatabase` and `$foreignDatabase` for dual DB connections

### 3. Database Access
- Always use `QueryBuilder` with `createNamedParameter()` for variables
- Use `$localDatabase` for local TYPO3 DB, `$foreignDatabase` for foreign DB
- Both are `TYPO3\CMS\Core\Database\Connection` instances

### 4. Controller Actions
- Must return `Psr\Http\Message\ResponseInterface`
- Use `$this->htmlResponse()`, `$this->jsonResponse()`, etc.

### 5. Events
- Create PSR-14 Event classes in `Classes/Event/`
- Register listeners in `Configuration/Services.yaml` or `Configuration/Features/*/Services.yaml`
- No legacy hooks

### 6. Features
- New optional functionality goes in `Classes/Features/<FeatureName>/`
- Create corresponding `Configuration/Features/<FeatureName>/Services.yaml`
- Import it in the main `Services.yaml` under `imports`

### 7. Commands
- Local-only commands: `Classes/Command/Local/`
- Foreign-only commands: `Classes/Command/Foreign/`
- Shared commands: `Classes/Command/`
- Register in `Services.yaml` with tag `console.command`

### 8. TCA
- Custom table definitions in `Configuration/TCA/`
- Overrides (for core/third-party tables) in `Configuration/TCA/Overrides/`
- Use modern TYPO3 v12 TCA types

## Recent Commits
```
474421e [RELEASE] Version 12.8.0
6487ea1 [META] Set the EM conf version number to 12.8.0
ee46e83 [DOCS] Update Changelog.md
4a2b9ae [DEV] use v12 compatible version of co-stack/stack-test for test setup
4eb1b2d [DEV] adjust composer.json for local development
```

## Commit Message Convention
Based on commit history, this project uses prefixes:
- `[FEATURE]` — New functionality
- `[BUGFIX]` — Bug fixes
- `[TASK]` — Technical tasks, refactoring
- `[DOCS]` — Documentation
- `[DEV]` — Development environment changes
- `[META]` — Version bumps, metadata
- `[RELEASE]` — Release commits

## Key Configuration Files
- `app/local/config/in2publish_core/LocalConfiguration.yaml` — in2publish local config
- `app/foreign/config/in2publish_core/ForeignConfiguration.yaml` — in2publish foreign config
- `ext_localconf.php` — Extension bootstrap
- `ext_tables.php` — Backend registration
- `ext_tables.sql` — Database schema
- `constants.php` — PHP constants (included via composer autoload `files`)
- `ext_conf_template.txt` — Extension Manager configuration
