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

This is a **monorepo** for the in2publish Content Publisher suite. The extension connects a **Local** (staging) TYPO3
instance with a **Foreign** (production) instance for content publishing.

Key concepts:
- **Local**: The Build/local TYPO3 instance where editors work
- **Foreign**: The Build/foreign TYPO3 instance that receives published content
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
├── Browser/       # Browser-based Codeception tests - legacy, to be replaced by Playwright
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
- `make restore` - Restore database and fileadmin from csv files
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
