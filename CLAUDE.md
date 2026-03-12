# Project: in2publish_core (Content Publisher)

Content publishing extension to connect stage (Local) and production (Foreign) TYPO3 servers.

## TYPO3 Configuration

**TYPO3 Version**: 13.4.x (LTS)
**PHP Version**: 8.2 - 8.4 (running: 8.4.8)
**Extension Version**: 13.2.2 (ext_emconf) / 13.3.0 (latest release)
**Extension Key**: in2publish_core
**Vendor Namespace**: In2code\In2publishCore
**Project Type**: Docker Compose + Composer Mode (monorepo)
**Package Path**: packages/in2publish_core/

### Project Architecture

This is a **monorepo** for the in2publish Content Publisher suite. The extension connects a **Local** (staging) TYPO3 instance with a **Foreign** (production) instance for content publishing.

Key concepts:
- **Local**: The local/staging TYPO3 instance where editors work
- **Foreign**: The foreign/live TYPO3 instance that receives published content
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

### Local Extensions (Monorepo packages/)

| Package | Description |
|---------|-------------|
| **in2publish_core** | Core publishing extension (this package) |
| **in2publish** | Meta package / distribution |
| **in2publish_http** | HTTP-based adapter for communication |
| **in2publish_local** | Native file publishing adapter (no SSH) |
| **in2publish_native** | SSH adapter using OS SSH/SCP binary |
| **in2publish_seclib** | SSH adapter using phpseclib |
| **in2publish_testing** | Scripts and helpers for testing |
| **build-essentials** | Build tools |
| **mysql-loader** | MySQL LOAD DATA / SELECT INTO OUTFILE via DBAL |
| **process-manager** | Parallel processing via Symfony Process |
| **stack-test** | Testing framework (PHPUnit ^9.6/^10.4/^11.5) |

### Docker Environment

- Docker Compose config: `.project/docker/docker-compose.darwin.yml` (symlinked)
- **Services**: local (httpd), local-php, foreign (httpd), foreign-php, mysql
- **Dual TYPO3 instances**: Local and Foreign running side-by-side
- `IN2PUBLISH_CONTEXT` env var distinguishes Local/Foreign

### Development Tools

- **PHPUnit**: Via stack-test package (^9.6/^10.4/^11.5)
- **Playwright**: Browser tests in `Tests/Playwright/`
- **Gulp + Sass**: Frontend build in `Resources/Private/Build/`
- No PHP CS Fixer, PHPStan, or Rector installed

### Test Structure

```
Tests/
├── Browser/       # Browser-based tests
├── Functional/    # Functional tests
├── Helper/        # Test helpers
├── Manual/        # Manual test procedures
├── Playwright/    # Playwright E2E tests
├── Unit/          # Unit tests
├── FunctionalTestCase.php
└── UnitTestCase.php
```

### Makefile Commands

Key targets:
- `make install-project` - Full project setup (Docker, DB, composer)
- `make start` / `make stop` / `make destroy` - Docker lifecycle
- `make composer-install` / `make composer-update` - Composer operations
- `make dump-dbs` / `make restore` - Database backup/restore
- `make typo3-clearcache` / `make typo3-rebuild-caches` - Cache management
- `make login-local-php` / `make login-foreign-php` - Shell access
- `make setup-qa` - QA tools setup
- `make urls` - Show project URLs

### TYPO3 v13 Guidelines

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


## Restrictions (Require Explicit Approval)
* **Dependencies:** Modifying `composer.json` or adding/removing dependencies.
* **Database Schema:** Deploying any changes to the database schema.
* **External APIs:** Calling live external APIs (permitted only within tests using mocks/stubs).

### Git Information

**Current Branch**: master (main branch: develop)

**Recent Commits:**
- 92856f62: [RELEASE] Version 13.3.0
- 301b3299: [META] Set the EM conf version number to 13.3.0
- 1acb0fd0: [DOCS] Update Changelog.md
- 86c966b6: [TASK] Merge dependabot pull-request for locutus
- ba1f9acb: Bump locutus from 2.0.32 to 2.0.39

**Commit Message Convention**: `[TYPE] Description` where TYPE is RELEASE, META, DOCS, TASK, BUGFIX, FEATURE, TEST
