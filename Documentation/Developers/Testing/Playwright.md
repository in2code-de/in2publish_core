# Playwright Testing for in2publish_core

We are migrating our browser tests from Codeception to Playwright to improve stability, speed, and debugging capabilities.

## Prerequisites

Playwright is installed automatically when running the project setup:

```bash
make setup
```

## Running Tests

All Playwright commands must be run from the `packages/in2publish_core` directory.

### Run All Tests
```bash
make playwright
```

### Run Specific Test File
```bash
make playwright FILE=content-publisher-tests.spec.ts
```

### Run in UI Mode (Recommended for Development)
The UI mode lets you explore tests, see the DOM snapshot for each step, and debug effectively.
```bash
npx playwright test --ui
make playwright-ui
```

### Run in Headed Mode
To watch the browser execution:
```bash
npx playwright test --headed
make playwright-watch
```

## Debugging

### Trace Viewer
By default, we capture traces on failure. To view the trace of a failed test:
```bash
make playwright-report
```
Click on the failed test and open the "Trace" tab to time-travel through the test execution.

### VS Code Extension
We recommend installing the [Playwright Test for VSCode](https://marketplace.visualstudio.com/items?itemName=ms-playwright.playwright) extension. It allows you to run and debug tests directly from the editor, set breakpoints, and inspect selectors.

## Environment Isolation
The `in2publish_core` tests operate in an isolated environment (`packages/in2publish_core/.env`).
- **Local System**: `https://local.v13.in2publish-core.de`
- **Foreign System**: `https://foreign.v13.in2publish-core.de`

The test helper `Environment.ts` automatically handles database resets (`make restore`) before tests run.

## Writing Tests
- Tests are located in `Tests/Playwright/`.
- Use `Typo3Helper` for common backend interactions (Login, Module navigation, Iframe handling).
- Use `Environment` helper to reset the state.
