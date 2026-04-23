# Playwright Tests for in2publish_core

This directory contains end-to-end browser tests for the Content Publisher community edition.

For full documentation on running, writing, and debugging tests — including enterprise tests
and the shared fixture architecture — see:

**[Documentation/Developers/Testing/Playwright.md](../../Documentation/Developers/Testing/Playwright.md)**

## TL;DR

Playwright is driven from the **monorepo root only**. A `.playwright.lock` at the root enforces
that core OR enterprise runs at any given time — not both.

```bash
make playwright-core                       # Run all tests
make playwright-core FILE="Tests/Playwright/modules/01-PublishOverview/publish-changed-content.spec.ts"
make playwright-core-ui                    # UI mode, http://localhost:9426
make playwright-core-report                # Open last HTML report
```