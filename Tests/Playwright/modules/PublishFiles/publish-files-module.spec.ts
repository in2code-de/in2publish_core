import * as path from 'path';
import { test, expect } from '@fixtures/setup-fixtures';
import config from '../../config';
import { Environment } from '@helpers/Environment';
import { restoreDatabases } from '@helpers/DbRestore';

test.describe('Publish Files Module', () => {

    test.beforeAll(async () => {
        await restoreDatabases();
        await Environment.reset();
    });

    /**
     * Test Case 2a + 2e: A newly uploaded file can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testNewlyUploadedFileCanBePublished
     */
    test('Newly uploaded file can be published', async ({ page, backend, browser }) => {

        await test.step('Given I am logged in to the Local Backend', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('And I navigate to the upload folder in Filelist', async () => {
            await backend.gotoModule('Filelist');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2e_missing_folder']);
        });

        await test.step('When I upload a file via context menu', async () => {
            // Right-click on the folder to open context menu
            const fileTree = page.locator('.scaffold-content-navigation-component');
            const folderNode = fileTree.locator('[role="treeitem"]').filter({ hasText: '2e_missing_folder' }).first();
            const label = folderNode.locator('.node-contentlabel').first();
            await label.click({ button: 'right' });

            // Click upload in context menu
            const uploadMenuItem = page.locator('.context-menu-item[data-contextmenu-id="root_upload"]');
            await expect(uploadMenuItem).toBeVisible({ timeout: 10000 });
            await uploadMenuItem.click();

            // Upload the file via the form
            const fileInput = backend.contentFrame.locator('input[name="upload_1[]"]');
            await fileInput.setInputFiles(path.resolve(__dirname, '../../Browser/files/carson-masterson-1540698-unsplash.jpg'));
            await backend.contentFrame.locator('#FileUploadController').locator('[type="submit"]').click();

            await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
        });

        await test.step('And I navigate to Publish Files for the folder', async () => {
            await backend.gotoModule('Publish Files');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2e_missing_folder']);

            const fileRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2e_missing_folder/carson-masterson-1540698-unsplash.jpg"]'
            );
            await expect(fileRow).toBeVisible({ timeout: 10000 });
            await expect(fileRow).toContainText('carson-masterson-1540698-unsplash.jpg');

            // Expand info
            const infoButton = fileRow.locator('[data-bs-toggle="collapse"]');
            await infoButton.click();
            await expect(backend.contentFrame.locator('body')).toContainText('Testcases');
        });

        await test.step('And I publish the file', async () => {
            const fileRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2e_missing_folder/carson-masterson-1540698-unsplash.jpg"]'
            );
            const publishButton = fileRow.locator('[data-easy-modal-title="Confirm publish"]');
            await publishButton.click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected file 1:/Testcases/2e_missing_folder/carson-masterson-1540698-unsplash.jpg has been published to the foreign system.'
            );
        });

        await test.step('Then the file should be visible in the Foreign Filelist', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2e_missing_folder']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-name="carson-masterson-1540698-unsplash.jpg"]')
                ).toBeVisible({ timeout: 10000 });
            });
        });
    });

    /**
     * Test Case 2b: A renamed file can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testRenamedFileCanBePublished
     */
    test('Renamed file can be published', async ({ page, backend, browser }) => {

        await test.step('Verify the file exists on Foreign first', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2b_published_file']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2b_published_file/bds-photo-1523151-unsplash.jpg"]')
                ).toBeVisible({ timeout: 10000 });
            });
        });

        await test.step('Given I am logged in', async () => {
            await backend.login(config.local.baseUrl);
        });

        await test.step('When I rename the file via context menu', async () => {
            await backend.gotoModule('Filelist');
            await backend.selectInFileStorageTree(['fileadmin', 'Testcases', '2b_published_file']);

            // Right-click the file row in the content frame
            await backend.contentFrame.locator(
                '[data-filelist-identifier="1:/Testcases/2b_published_file/bds-photo-1523151-unsplash.jpg"]'
            ).click({ button: 'right' });

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

            await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
        });

        await test.step('And I verify the renamed file in Filelist', async () => {
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
            await expect(fileRow.locator('td.col-state .rounded-pill')).toContainText('Changed');
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

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected file 1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg has been published to the foreign system.'
            );

            // Both local and foreign should now show the renamed file
            const publishedRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"]'
            );
            await expect(publishedRow.locator('td.col-filename--local')).toContainText('renamed-1523151-unsplash.jpg');
            await expect(publishedRow.locator('td.col-filename--foreign')).toContainText('renamed-1523151-unsplash.jpg');
        });

        await test.step('Then the renamed file should be visible on Foreign', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2b_published_file']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"]')
                ).toBeVisible({ timeout: 10000 });
            });
        });
    });

    /**
     * Test Case 2c: A moved file can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testMovedFileCanBePublished
     */
    test('Moved file can be published', async ({ backend, browser }) => {

        await test.step('Verify the file exists in source folder on Foreign', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2c_source_folder']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2c_source_folder/MovedFile_2c.txt"]')
                ).toBeVisible({ timeout: 10000 });
            });
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
            await fileRow.locator('a:has-text("Publish")').click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected file 1:/Testcases/2c_target_folder/MovedFile_2c.txt has been published to the foreign system.'
            );

            // After publishing, state should be unchanged
            const publishedRow = backend.contentFrame.locator('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"]');
            await expect(publishedRow.locator('td.col-state .rounded-pill')).toContainText('Unchanged');
        });

        await test.step('Then the file should be in the target folder on Foreign', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2c_target_folder']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2c_target_folder/MovedFile_2c.txt"]')
                ).toBeVisible({ timeout: 10000 });
            });
        });
    });

    /**
     * Test Case 2d: A deleted file can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testDeletedFileCanBePublished
     */
    test('Deleted file can be published', async ({ backend, browser }) => {

        await test.step('Verify the file exists on Foreign first', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2d_deleted_file']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]')
                ).toBeVisible({ timeout: 10000 });
            });
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
            await fileRow.locator('a:has-text("Publish")').click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected file 1:/Testcases/2d_deleted_file/2d_deleted_file.txt has been published to the foreign system.'
            );

            // The file row should no longer exist
            await expect(
                backend.contentFrame.locator('[data-id="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]')
            ).not.toBeVisible();
        });

        await test.step('Then the file should be gone from Foreign', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2d_deleted_file']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]')
                ).not.toBeVisible();
            });
        });
    });

    /**
     * Test Case 2f: A deleted folder can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testDeletedFolderCanBePublished
     */
    test('Deleted folder can be published', async ({ backend, browser }) => {

        await test.step('Verify the folder exists on Foreign first', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2f_delete_folder']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2f_delete_folder/DeletedFile_2f.txt"]')
                ).toBeVisible({ timeout: 10000 });
            });
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
            await folderRow.locator('a:has-text("Publish")').click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected folder 1:/Testcases/2f_delete_folder/ has been published to the foreign system.'
            );

            // The folder row should no longer exist
            await expect(
                backend.contentFrame.locator('[data-id="1:/Testcases/2f_delete_folder/"]')
            ).not.toBeVisible();
        });

        await test.step('Then the folder should be gone from Foreign', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-id="1:/Testcases/2f_delete_folder/"]')
                ).not.toBeVisible();
            });
        });
    });

    /**
     * Test Case 2g: A moved/renamed folder with file can be published.
     * Mirrors Tests/Browser/PublishFilesModuleTest.php::testRenamedFolderCanBePublished
     */
    test('Moved folder with file can be published', async ({ backend, browser }) => {

        await test.step('Verify the folder exists on Foreign in original location', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2g_moved_folder_with_file']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]')
                ).toBeVisible({ timeout: 10000 });
            });
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
            await fileRow.locator('a:has-text("Publish")').click();

            await backend.clickModalButton('Publish');
            await backend.waitUntilPublishingFinished();

            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected file 1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt has been published to the foreign system.'
            );

            // After publishing, state should be unchanged
            const publishedRow = backend.contentFrame.locator(
                '[data-id="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]'
            );
            await expect(publishedRow.locator('td.col-state .rounded-pill')).toContainText('Unchanged');
        });

        await test.step('Then the file should be in the new location on Foreign', async () => {
            await backend.withForeignContext(browser, async (foreignBackend) => {
                await foreignBackend.gotoModule('Filelist');
                await foreignBackend.selectInFileStorageTree(['fileadmin', 'Testcases', '2g_target_folder', '2g_moved_folder_with_file']);

                await expect(
                    foreignBackend.contentFrame.locator('[data-filelist-identifier="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]')
                ).toBeVisible({ timeout: 10000 });
            });
        });
    });
});
