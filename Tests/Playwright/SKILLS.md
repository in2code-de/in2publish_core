# Writing Playwright Tests for in2publish_core

## Project Setup

- **Shared package**: `@in2code/typo3-playwright` (from tarball, see package.json)
- **Playwright version**: 1.57+
- **Test location**: `Tests/Playwright/modules/<Area>/<test-name>.spec.ts`
- **Runner**: `make playwright` from this extension directory, or `make playwright-core` from the
  monorepo root. Both paths run inside this extension's own Docker stack and restore DB/fileadmin
  from the monorepo root sources.

---

## Import Pattern

Always import `{ test, expect }` from the project's fixtures file, **not** from `@playwright/test`:

```typescript
import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { execMake } from '../../shared/helpers';
```

---

## Test Structure

```typescript
test.describe('Publish Changed Page Properties', () => {

    test.beforeAll(() => {
        execMake('restore');  // Restores DB + fileadmin via the Makefile
    });

    test('Test case description', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I ...', async () => {
            // actions
        });

        await test.step('Then ...', async () => {
            // assertions
        });
    });
});
```

**Rules:**
- Tests run sequentially (`workers: 1`, `fullyParallel: false`) — DB state is shared
- `test.beforeAll` resets the environment once per describe block
- Use `test.step()` for complex multi-step flows

---

## BackendPage API

```typescript
// Injected via `backend` fixture in test signature
const { backend } = fixtures;

// Login to local backend
await backend.login(config.local.baseUrl);

// Navigate to a module
await backend.gotoModule('Layout');           // Resolves to 'Layout' in TYPO3 v14
await backend.gotoModule('Publish Overview');
await backend.gotoModule('Publish Files');
await backend.gotoModule('List');            // Resolves to 'Records' in TYPO3 v14
await backend.gotoModule('Filelist');        // Resolves to 'Media' in TYPO3 v14
await backend.gotoModule('Publish Redirects');

// Search and select in page tree
await backend.searchInPageTreeAndSelectFirstOccurrence('1a Page properties - changed');

// Navigate file storage tree (in2publish-specific)
await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2b_published_file']);

// Wait for publishing loading overlay to disappear
await backend.waitUntilPublishingFinished();

// Click a TYPO3 modal button
await backend.clickModalButton('Publish');
await backend.clickModalButton('OK');

// Access content iframe (always use this for content inside backend)
backend.contentFrame.locator('.something')
```

---

## Content Iframe Pattern

TYPO3 backend content lives in `#typo3-contentIframe`. Always use `backend.contentFrame` to interact with it:

```typescript
// Good
await expect(backend.contentFrame.locator('[data-record-identifier="pages-5"]')).toBeVisible();
await expect(backend.contentFrame.locator('body')).toContainText('The selected record has been published successfully');

// Never do this (wrong — targets main document, not iframe)
await expect(page.locator('[data-record-identifier="pages-5"]')).toBeVisible();
```

---

## Dual-Instance Testing (Local → Foreign)

For verifying published content on the Foreign backend:

```typescript
const foreignContext = await browser.newContext();
const foreignPage = await foreignContext.newPage();
const foreignBackend = new BackendPage(foreignPage);

await foreignBackend.login(config.foreign.baseUrl);
await foreignBackend.gotoModule('Page');
await foreignBackend.searchInPageTreeAndSelectFirstOccurrence('My Changed Page');

await expect(foreignBackend.contentFrame.locator('body')).toContainText('expected content');

await foreignContext.close();  // Always close to free resources
```

---

## Publish Overview Workflow

Standard pattern for publishing a record:

```typescript
// 1. Navigate to the page
await backend.gotoModule('Page');
await backend.searchInPageTreeAndSelectFirstOccurrence('My Page Title');

// 2. Open Publish Overview
await backend.gotoModule('Publish Overview');
await expect(
    backend.contentFrame.locator('text=TYPO3 Content Publisher - publish pages and records overview')
).toBeVisible({ timeout: 10000 });

// 3. Find the record row
const recordRow = backend.contentFrame.locator('[data-record-identifier="pages-5"]');
await expect(recordRow).toBeVisible();

// 4. Optionally expand dirty properties
const infoIcon = recordRow.locator('[data-action="opendirtypropertieslistcontainer"]');
await infoIcon.click();
await expect(backend.contentFrame.locator('.in2publish-dirty-properties-local')).toContainText('expected value');

// 5. Publish
const arrowRight = backend.contentFrame.locator('[data-record-identifier="pages-5"] .icon-actions-arrow-right');
await expect(arrowRight).toBeVisible();
await arrowRight.click();

// 6. Verify success
await expect(backend.contentFrame.locator('body')).toContainText('The selected record has been published successfully');
```

---

## Publish Files Workflow

```typescript
// Navigate to Publish Files module and select folder in file tree
await backend.gotoModule('Publish Files');
await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2b_published_file']);

// Find file row by data-id (files)
const fileRow = backend.contentFrame.locator('[data-id="1:/Testcases/2b_published_file/filename.jpg"]');

// Check state badge: 'Changed', 'Moved', 'Deleted', 'Unchanged'
await expect(fileRow.locator('td.col-state .rounded-pill')).toContainText('Changed');

// Local/foreign filename columns
await expect(fileRow.locator('td.col-filename--local')).toContainText('new-name.jpg');
await expect(fileRow.locator('td.col-filename--foreign')).toContainText('old-name.jpg');

// Publish the file
await fileRow.locator('[data-easy-modal-title="Confirm publish"]').click();
await backend.clickModalButton('Publish');
await backend.waitUntilPublishingFinished();

// Verify success message
await expect(backend.contentFrame.locator('body')).toContainText(
    'The selected file 1:/Testcases/... has been published to the foreign system.'
);

// Folders use trailing slash: [data-id="1:/Testcases/2f_delete_folder/"]
// Foreign filelist verification uses:
//   [data-filelist-identifier="1:/path/to/file.jpg"]  or
//   [data-filelist-name="filename.jpg"]
```

---

## Common Selectors

| Element | Selector |
|---------|----------|
| Record row | `[data-record-identifier="pages-5"]` |
| Info/expand icon | `[data-action="opendirtypropertieslistcontainer"]` |
| Changed badge | `.in2publish-badge--changed` |
| Unchanged badge | `.in2publish-badge--unchanged` |
| Publish arrow | `.icon-actions-arrow-right` |
| Dependency warning | `.icon-actions-exclamation-triangle-alt` |
| Local dirty props | `.in2publish-dirty-properties-local` |
| Foreign dirty props | `.in2publish-dirty-properties-foreign` |
| Loading overlay | `.in2publish-loading-overlay` |
| File row | `[data-id="1:/path/to/file.jpg"]` |
| Folder row | `[data-id="1:/path/to/folder/"]` |
| File state badge | `td.col-state .rounded-pill` |
| File local name | `td.col-filename--local` |
| File foreign name | `td.col-filename--foreign` |
| Publish button (file) | `[data-easy-modal-title="Confirm publish"]` |
| FormEngine input | `[data-formengine-input-name="data[table][uid][field]"]` |

---

## Wait Patterns

```typescript
// Wait for element to appear
await expect(locator).toBeVisible({ timeout: 10000 });

// Wait for text to appear in body
await expect(backend.contentFrame.locator('body')).toContainText('text', { timeout: 10000 });

// Wait for loading overlay to disappear (after file publishing)
await backend.waitUntilPublishingFinished();

// Wait for module to load (gotoModule() already handles this)

// Avoid: page.waitForTimeout(2000) — use explicit waitFor conditions instead
```

---

## Common Mistakes

- **Don't import from `@playwright/test`** — import from `../../fixtures/setup-fixtures`
- **Don't skip `test.step()`** — use it to document multi-step flows
- **Don't use `waitForTimeout()`** — use `toBeVisible()`, `toContainText()`, or `waitUntilPublishingFinished()`
- **Don't forget `foreignContext.close()`** — always clean up new browser contexts
- **Don't hardcode UIDs** without a comment — UIDs come from the DB dump, add a comment explaining where it comes from
- **Don't interact with main `page.locator()`** for backend content — always use `backend.contentFrame`

---

## Environment & Config

```typescript
config.local.baseUrl   // Local backend URL (e.g. https://local.v13.in2publish-core.de/typo3/)
config.foreign.baseUrl // Foreign backend URL (e.g. https://foreign.v13.in2publish-core.de/typo3/)

// Restore is driven through Makefile targets, run from the Playwright container
// (which has the Docker CLI + socket). execMake runs `make <target>` at the extension root.
import { execMake } from '../../shared/helpers';

// Full restore (DB + fileadmin):
test.beforeAll(() => {
    execMake('restore');
});

// For per-test DB-only reset (e.g. tests that modify data):
test.beforeEach(() => {
    execMake('restore-db');
});

// Run an arbitrary command in a compose service (e.g. ad-hoc SQL):
import { execInContainer } from '../../shared/helpers';
execInContainer('in2publish_core', 'mysql', `mysql -uroot -proot local -e "UPDATE ..."`);
```

---

## Test File Naming

- Use kebab-case: `publish-changed-page-properties.spec.ts`
- Location: `Tests/Playwright/modules/<Area>/`
- Module areas: `PublishOverview/`, `PublishFiles/`, `RedirectsModule/`, `Regression/`
