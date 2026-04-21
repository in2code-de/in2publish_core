import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import config from '../../config';
import { fullRestore } from '../../helpers/direct-restore';

test.describe('Publish Files Module', () => {

    // File operations + foreign verification + DB/fileadmin restore overhead needs more time
    test.describe.configure({ timeout: 120000 });

    // Each file test modifies state (upload, rename, move, delete), so restore before each test.
    // Uses direct restore for both databases and fileadmin to ensure a clean state.
    // Environment.reset() is skipped in CI, so we must restore explicitly.
    test.beforeEach(async ({ backend }) => {
        await fullRestore();
        // Flush TYPO3 caches via the backend UI after restore.
        // The PHP-FPM processes may have OPcache or in-memory state from the previous test
        // that doesn't reflect the freshly restored database/fileadmin state.
        await backend.login(config.local.baseUrl);
        await backend.clearCaches();
    });

    /**
     * Test Case 2e: A new file (only on Local) can be published.
     * Uses MissingFile_2e.txt which exists in Local fileadmin backup but not in Foreign.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testNewlyUploadedFileCanBePublished
     */
    test('Newly uploaded file can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('And I navigate to Publish Files for the missing-file folder', async () => {
            await backend.gotoModule('Publish Files');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2e_missing_folder']);

            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('TYPO3 Content Publisher - Publish files', { timeout: 10000 });

            const fileRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2e_missing_folder/MissingFile_2e.txt"]'
            );
            await expect(fileRow).toBeVisible({ timeout: 10000 });
            await expect(fileRow.locator('td.col-state .rounded-pill')).toContainText('New');

            // Expand info
            const infoButton = fileRow.locator('[data-bs-toggle="collapse"]');
            await infoButton.click();
            await expect(backend.contentFrame.locator('body')).toContainText('Testcases');
        });

        await test.step('And I publish the file', async () => {
            const fileRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2e_missing_folder/MissingFile_2e.txt"]'
            );
            const publishButton = fileRow.locator('[data-easy-modal-title="Confirm publish"]');
            await publishButton.click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            // Wait for the redirect back to the index page and verify the file is now "Unchanged"
            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - Publish files')
            ).toBeVisible({ timeout: 30000 });

            const publishedRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2e_missing_folder/MissingFile_2e.txt"]'
            );
            await expect(publishedRow.locator('td.col-state .rounded-pill')).toContainText(
                'Unchanged', { timeout: 10000 }
            );
        });

        await test.step('Then the file should be visible in the Foreign Filelist', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);

            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Filelist');
            await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2e_missing_folder']);

            await expect(
                foreignBackend.contentFrame.locator('[data-filelist-name="MissingFile_2e.txt"]')
            ).toBeVisible({ timeout: 10000 });

            await foreignContext.close();
        });
    });

    /**
     * Test Case 2b: A renamed file can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testRenamedFileCanBePublished
     */
    test('Renamed file can be published', async ({ page, backend, browser }) => {

        await test.step('Verify the file exists on Foreign first', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Filelist');
            await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2b_published_file']);

            await expect(
                foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2b_published_file/bds-photo-1523151-unsplash.jpg"]')
            ).toBeVisible({ timeout: 10000 });

            await foreignContext.close();
        });

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I rename the file via context menu', async () => {
            await backend.gotoModule('Filelist');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2b_published_file']);

            // Check if the file is already renamed (e.g. on retry after a previous attempt succeeded)
            const originalFile = backend.contentFrame.locator(
                '[data-filelist-identifier="1:/Testcases/2b_published_file/bds-photo-1523151-unsplash.jpg"]'
            );
            const alreadyRenamed = await originalFile.isVisible().catch(() => false);

            if (alreadyRenamed) {
                // Right-click the file row in the content frame
                await originalFile.click({ button: 'right' });

                // Click rename in the context menu (which is in the main document)
                const renameMenuItem = page.locator('.context-menu-item[data-contextmenu-id="root_rename"]');
                await expect(renameMenuItem).toBeVisible({ timeout: 10000 });
                await renameMenuItem.click();

                // Fill in the new name in the modal
                // TYPO3 v13 rename modal: uses typo3-backend-modal with button[name="rename"] (not type="submit")
                const modal = page.locator('typo3-backend-modal .modal, .modal.show');
                await expect(modal).toBeVisible({ timeout: 10000 });
                const nameInput = modal.locator('input[name="name"]');
                await nameInput.clear();
                await nameInput.fill('renamed-1523151-unsplash.jpg');
                await modal.locator('button[name="rename"]').click();

                // Wait for the rename to complete and page to stabilize
                await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
                await page.waitForTimeout(2000);
            }
            // else: file was already renamed from a previous attempt, skip rename step
        });

        await test.step('And I verify the renamed file in Filelist', async () => {
            // Reload the page to ensure clean state after rename modal
            await backend.gotoModule('Filelist');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2b_published_file']);

            await expect(
                backend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"]')
            ).toBeVisible({ timeout: 10000 });
        });

        await test.step('And I navigate to Publish Files and verify the change', async () => {
            await backend.gotoModule('Publish Files');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2b_published_file']);

            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('TYPO3 Content Publisher - Publish files', { timeout: 10000 });

            const fileRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"]'
            );
            await expect(fileRow).toBeVisible({ timeout: 10000 });
            await expect(fileRow.locator('td.col-state .rounded-pill')).toContainText('Changed', { timeout: 5000 });
            await expect(fileRow.locator('td.col-filename--local')).toContainText('renamed-1523151-unsplash.jpg');
            await expect(fileRow.locator('td.col-filename--foreign')).toContainText('bds-photo-1523151-unsplash.jpg');
        });

        await test.step('And I publish the renamed file', async () => {
            const fileRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"]'
            );
            const publishButton = fileRow.locator('[data-easy-modal-title="Confirm publish"]');
            await publishButton.click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            // Wait for the redirect back to index and verify publish succeeded
            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - Publish files')
            ).toBeVisible({ timeout: 30000 });

            // Both local and foreign should now show the renamed file
            const publishedRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"]'
            );
            await expect(publishedRow.locator('td.col-filename--local')).toContainText('renamed-1523151-unsplash.jpg');
            await expect(publishedRow.locator('td.col-filename--foreign')).toContainText('renamed-1523151-unsplash.jpg');
        });

        await test.step('Then the renamed file should be visible on Foreign', async () => {
            // Verify via direct HTTP URL check (avoids FAL indexing delays in Filelist UI)
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const fileUrl = config.foreign.baseUrl.replace('/typo3/', '')
                + '/fileadmin/Testcases/2b_published_file/renamed-1523151-unsplash.jpg';
            const response = await foreignPage.goto(fileUrl);
            expect(response?.status()).toBe(200);
            await foreignContext.close();
        });
    });

    /**
     * Test Case 2c: A moved file can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testMovedFileCanBePublished
     */
    test('Moved file can be published', async ({ page, backend, browser }) => {

        await test.step('Verify the file exists in source folder on Foreign', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Filelist');
            await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2c_source_folder']);

            await expect(
                foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2c_source_folder/MovedFile_2c.txt"]')
            ).toBeVisible({ timeout: 10000 });

            await foreignContext.close();
        });

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I navigate to Publish Files for the target folder', async () => {
            await backend.gotoModule('Publish Files');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2c_target_folder']);

            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('TYPO3 Content Publisher - Publish files', { timeout: 10000 });

            const fileRow = backend.contentFrame.locator('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"]');
            await expect(fileRow.locator('td.col-state .rounded-pill')).toContainText('Moved');
            await expect(fileRow.locator('td.col-filename--local')).toContainText('/Testcases/2c_target_folder/MovedFile_2c.txt');
            await expect(fileRow.locator('td.col-filename--foreign')).toContainText('/Testcases/2c_source_folder/MovedFile_2c.txt');
        });

        await test.step('And I publish the moved file', async () => {
            const fileRow = backend.contentFrame.locator('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"]');
            await fileRow.locator('[data-easy-modal-title="Confirm publish"]').click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            // Wait for the redirect back to index and verify publish succeeded
            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - Publish files')
            ).toBeVisible({ timeout: 30000 });

            // After publishing, state should be unchanged
            const publishedRow = backend.contentFrame.locator('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"]');
            await expect(publishedRow.locator('td.col-state .rounded-pill')).toContainText('Unchanged', { timeout: 10000 });
        });

        await test.step('Then the file should be in the target folder on Foreign', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Filelist');
            await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2c_target_folder']);

            await expect(
                foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2c_target_folder/MovedFile_2c.txt"]')
            ).toBeVisible({ timeout: 10000 });

            await foreignContext.close();
        });
    });

    /**
     * Test Case 2d: A deleted file can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testDeletedFileCanBePublished
     */
    test('Deleted file can be published', async ({ page, backend, browser }) => {

        await test.step('Verify the file exists on Foreign first', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Filelist');
            await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2d_deleted_file']);

            await expect(
                foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]')
            ).toBeVisible({ timeout: 10000 });

            await foreignContext.close();
        });

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I navigate to Publish Files and verify the deleted state', async () => {
            await backend.gotoModule('Publish Files');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2d_deleted_file']);

            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('TYPO3 Content Publisher - Publish files', { timeout: 10000 });

            const fileRow = backend.contentFrame.locator('[data-id="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]');
            await expect(fileRow.locator('td.col-state .rounded-pill')).toContainText('Deleted');
        });

        await test.step('And I publish the deleted file', async () => {
            const fileRow = backend.contentFrame.locator('[data-id="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]');
            await fileRow.locator('[data-easy-modal-title="Confirm publish"]').click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            // Wait for the redirect back to index
            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - Publish files')
            ).toBeVisible({ timeout: 30000 });

            // The file row should no longer exist (deleted from both sides)
            await expect(
                backend.contentFrame.locator('[data-id="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]')
            ).not.toBeVisible();
        });

        await test.step('Then the file should be gone from Foreign', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Filelist');
            await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2d_deleted_file']);

            await expect(
                foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]')
            ).not.toBeVisible();

            await foreignContext.close();
        });
    });

    /**
     * Test Case 2f: A deleted folder can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testDeletedFolderCanBePublished
     */
    test('Deleted folder can be published', async ({ page, backend, browser }) => {
        test.setTimeout(180000);

        await test.step('Verify the folder exists on Foreign first', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Filelist');
            await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2f_delete_folder']);

            await expect(
                foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2f_delete_folder/DeletedFile_2f.txt"]')
            ).toBeVisible({ timeout: 10000 });

            await foreignContext.close();
        });

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I navigate to Publish Files for Testcases and verify the deleted folder', async () => {
            await backend.gotoModule('Publish Files');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases']);

            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('TYPO3 Content Publisher - Publish files', { timeout: 10000 });

            const folderRow = backend.contentFrame.locator('[data-id="1:/Testcases/2f_delete_folder/"]');
            await expect(folderRow.locator('td.col-state .rounded-pill')).toContainText('Deleted');
            await expect(folderRow.locator('td.col-filename--local')).toContainText('---');
            await expect(folderRow.locator('td.col-filename--foreign')).toContainText('2f_delete_folder');
        });

        await test.step('And I publish the deleted folder', async () => {
            const folderRow = backend.contentFrame.locator('[data-id="1:/Testcases/2f_delete_folder/"]');
            await folderRow.locator('[data-easy-modal-title="Confirm publish"]').click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            // Wait for the redirect back to index
            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - Publish files')
            ).toBeVisible({ timeout: 30000 });

            // The folder row should no longer exist (deleted from both sides)
            await expect(
                backend.contentFrame.locator('[data-id="1:/Testcases/2f_delete_folder/"]')
            ).not.toBeVisible();
        });

        await test.step('Then the folder should be gone from Foreign', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Filelist');
            await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases']);

            await expect(
                foreignBackend.contentFrame.locator('[data-id="1:/Testcases/2f_delete_folder/"]')
            ).not.toBeVisible();

            await foreignContext.close();
        });
    });

    /**
     * Test Case 2g: A moved/renamed folder with file can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testRenamedFolderCanBePublished
     */
    test('Moved folder with file can be published', async ({ page, backend, browser }) => {

        await test.step('Verify the folder exists on Foreign in original location', async () => {
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const foreignBackend = new BackendPage(foreignPage);
            await foreignBackend.login(config.foreign.baseUrl);
            await foreignBackend.gotoModule('Filelist');
            await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2g_moved_folder_with_file']);

            await expect(
                foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]')
            ).toBeVisible({ timeout: 10000 });

            await foreignContext.close();
        });

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I navigate to Publish Files for the target folder and verify the moved state', async () => {
            await backend.gotoModule('Publish Files');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2g_target_folder', '2g_moved_folder_with_file']);

            await expect(
                backend.contentFrame.locator('body')
            ).toContainText('TYPO3 Content Publisher - Publish files', { timeout: 10000 });

            const fileRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]'
            );
            await expect(fileRow.locator('td.col-state .rounded-pill')).toContainText('Moved');
            await expect(fileRow.locator('td.col-filename--local')).toContainText(
                '/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt'
            );
            await expect(fileRow.locator('td.col-filename--foreign')).toContainText(
                '/Testcases/2g_moved_folder_with_file/MovedFileInFolder_2g.txt'
            );
        });

        await test.step('And I publish the moved file', async () => {
            const fileRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]'
            );
            await fileRow.locator('[data-easy-modal-title="Confirm publish"]').click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            // Wait for the redirect back to index and verify publish succeeded
            await expect(
                backend.contentFrame.locator('text=TYPO3 Content Publisher - Publish files')
            ).toBeVisible({ timeout: 30000 });

            // After publishing, state should be unchanged
            const publishedRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]'
            );
            await expect(publishedRow.locator('td.col-state .rounded-pill')).toContainText('Unchanged', { timeout: 10000 });
        });

        await test.step('Then the file should be in the new location on Foreign', async () => {
            // Verify via direct HTTP request that the file physically exists on Foreign.
            // Filelist requires FAL indexing which is unreliable after a move operation,
            // but the publish physically moves the file on disk, so a direct URL check is authoritative.
            const foreignContext = await browser.newContext({ ignoreHTTPSErrors: true });
            const foreignPage = await foreignContext.newPage();
            const fileUrl = config.foreign.baseUrl.replace('/typo3/', '')
                + '/fileadmin/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt';
            const response = await foreignPage.goto(fileUrl);
            expect(response?.status()).toBe(200);

            await foreignContext.close();
        });
    });
});
