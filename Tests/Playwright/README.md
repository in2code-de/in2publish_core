# Playwright Tests for in2publish_core

This directory contains end-to-end browser tests for the Content Publisher community edition.

For full documentation on running, writing, and debugging tests — including enterprise tests
and the shared fixture architecture — see:

**[Documentation/Developers/Testing/Playwright.md](../../Documentation/Developers/Testing/Playwright.md)**

## TL;DR

Playwright runs in the `in2publish_core` Docker stack. You can invoke it from this directory or via
the monorepo root wrapper. Core uses a package-local `.playwright.lock` and restores only the core
local/foreign TYPO3 instances from the monorepo root dump/fileadmin sources. `make restore`
(and `make restore-db`) also recreate the foreign-only empty tables defined in the
`FOREIGN_ONLY_EMPTY_TABLES` Makefile variable.

```bash
make setup-tests                          # From this directory
make playwright
make playwright FILE="Tests/Playwright/modules/01-PublishOverview/publish-changed-content.spec.ts"
make playwright-ui                        # UI mode, http://localhost:9425
make playwright-report                    # Open last HTML report

# Or from the monorepo root
make playwright-core
```
