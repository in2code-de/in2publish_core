<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\Test\Constraint\Visibility\ElementIsVisible;
use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use In2code\In2publishCore\Tests\Helper\ContentPublisherHelper;
use In2code\In2publishTests\AcceptanceTester;

use function substr;

class PublishFilesModuleTest extends AbstractBrowserTestCase
{
    /**
     * Test Case 2a + 2e
     */
    public function testNewlyUploadedFileCanBePublished(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3/', 'admin', 'password');

        TYPO3Helper::selectModuleByText($localDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($localDriver, ['fileadmin', 'Testcases', '2e_missing_folder']);

        // Right-click on the folder in the file tree
        TYPO3Helper::selectInFileStorageTree(
            $localDriver,
            ['fileadmin', 'Testcases', '2e_missing_folder'],
            static function (WebDriver $driver, RemoteWebElement $folderElement): void {
                $driver->contextClick($folderElement);
            },
        );

        // Wait for the context menu to appear in the main document
        $localDriver->wait(10, 500)->until(
            ElementIsVisible::resolve(WebDriverBy::cssSelector('.context-menu-item[data-contextmenu-id="root_upload"]'))
        );

        // Click the upload button in the context menu
        $localDriver->click(WebDriverBy::cssSelector('.context-menu-item[data-contextmenu-id="root_upload"]'));

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $file = '/app/Tests/Browser/files/carson-masterson-1540698-unsplash.jpg';
            // https://www.selenium.dev/documentation/webdriver/elements/file_upload/#:~:text=Because%20Selenium%20cannot%20interact%20with,file%20that%20will%20be%20uploaded.
            $driver->findElement(WebDriverBy::name('upload_1[]'))->sendKeys($file);
            $driver->submitForm(WebDriverBy::id('FileUploadController'));
        });

        TYPO3Helper::refreshFileStorageTree($localDriver);
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Files');
        TYPO3Helper::selectInFileStorageTree($localDriver, ['fileadmin', 'Testcases', '2e_missing_folder']);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $fileSelector = WebDriverBy::cssSelector(
                '[data-id="1:/Testcases/2e_missing_folder/carson-masterson-1540698-unsplash.jpg"',
            );
            self::assertElementIsVisible($driver, $fileSelector);
            self::assertElementContains($driver, 'carson-masterson-1540698-unsplash.jpg', $fileSelector);
            $element = $driver->findElement($fileSelector);
            $infoButton = $element->findElement(WebDriverBy::cssSelector('[data-bs-toggle="collapse"]'));
            $infoButton->click();
            $href = $infoButton->getAttribute('href');
            self::assertElementContains($driver, 'Testcases', WebDriverBy::id(substr($href, 1)));
        });
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $fileSelector = WebDriverBy::cssSelector(
                '[data-id="1:/Testcases/2e_missing_folder/carson-masterson-1540698-unsplash.jpg"',
            );
            $fileRow = $driver->findElement($fileSelector);
            $filePublishButton = $fileRow->findElement(
                WebDriverBy::cssSelector('[data-easy-modal-title="Confirm publish"]'),
            );
            $filePublishButton->click();
        });
        TYPO3Helper::clickModalButton($localDriver, 'Publish');
        ContentPublisherHelper::waitUntilPublishingFinished($localDriver);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains(
                $driver,
                'The selected file 1:/Testcases/2e_missing_folder/carson-masterson-1540698-unsplash.jpg has been published to the foreign system.',
            );
        });

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2e_missing_folder']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::cssSelector('[data-filelist-name="carson-masterson-1540698-unsplash.jpg"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }

    /**
     * Test Case 2b
     *
     */
    public function testRenamedFileCanBePublished(): void
    {
        // Assert file exists on foreign
        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2b_published_file']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::cssSelector(
                    '[data-filelist-identifier="1:/Testcases/2b_published_file/bds-photo-1523151-unsplash.jpg"]',
                ),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);

        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($localDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($localDriver, ['fileadmin', 'Testcases', '2b_published_file']);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->contextClick(
                WebDriverBy::cssSelector(
                    '[data-filelist-identifier="1:/Testcases/2b_published_file/bds-photo-1523151-unsplash.jpg"]',
                ),
            );
        });


        $localDriver->wait(5, 500)->until(
            ElementIsVisible::resolve(WebDriverBy::cssSelector('.context-menu-item[data-contextmenu-id="root_rename"]'))
        );

        $localDriver->click(WebDriverBy::cssSelector('.context-menu-item[data-contextmenu-id="root_rename"]'));

        TYPO3Helper::waitUntilModalIsOpen($localDriver);
        $localDriver->submitForm(WebDriverBy::xpath('//typo3-backend-modal/div[contains(@class, "modal")]//form'), [
            'name' => 'renamed-1523151-unsplash.jpg',
        ]);

        TYPO3Helper::selectModuleByText($localDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($localDriver, ['fileadmin', 'Testcases', '2b_published_file']);
        TYPO3Helper::waitUntilContentIFrameIsLoaded($localDriver);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::cssSelector(
                    '[data-filelist-identifier="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"]',
                ),
            );
        });

        TYPO3Helper::selectModuleByText($localDriver, 'Publish Files');
        TYPO3Helper::selectInFileStorageTree($localDriver, ['fileadmin', 'Testcases', '2b_published_file']);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - Publish files');
            self::assertElementContains($driver, 'Changed', WebDriverBy::cssSelector('[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"] td.col-state .rounded-pill'));
            self::assertElementContains($driver, 'renamed-1523151-unsplash.jpg', WebDriverBy::cssSelector('[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"] td.col-filename--local'));
            self::assertElementContains($driver, 'bds-photo-1523151-unsplash.jpg', WebDriverBy::cssSelector('[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"] td.col-filename--foreign'));
        });
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $fileSelector = WebDriverBy::cssSelector(
                '[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"]',
            );
            $fileRow = $driver->findElement($fileSelector);
            $filePublishButton = $fileRow->findElement(
                WebDriverBy::cssSelector('[data-easy-modal-title="Confirm publish"]'),
            );
            $filePublishButton->click();
        });

        ContentPublisherHelper::waitUntilPublishingFinished($localDriver);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains(
                $driver,
                'The selected file 1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg has been published to the foreign system.',
            );
            self::assertElementContains($driver, 'renamed-1523151-unsplash.jpg', WebDriverBy::cssSelector('[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"] td.col-filename--local'));
            self::assertElementContains($driver, 'renamed-1523151-unsplash.jpg', WebDriverBy::cssSelector('[data-id="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"] td.col-filename--foreign'));
        });

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2b_published_file']);
        // Workaround: when selecting file for the first time, there is an error "File has been deleted"
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2b_published_file']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::cssSelector(
                    '[data-filelist-identifier="1:/Testcases/2b_published_file/renamed-1523151-unsplash.jpg"]',
                ),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }


    /**
     * Test Case 2c
     */
    public function testMovedFileCanBePublished(): void
    {
        // Assert file on foreign
        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2c_source_folder']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::cssSelector('[data-filelist-identifier="1:/Testcases/2c_source_folder/MovedFile_2c.txt"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);

        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Files');
        TYPO3Helper::selectInFileStorageTree($localDriver, ['fileadmin', 'Testcases', '2c_target_folder']);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - Publish files');
            self::assertElementContains($driver, 'Moved', WebDriverBy::cssSelector('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"] td.col-state .rounded-pill'));
            self::assertElementEquals($driver, '/Testcases/2c_target_folder/MovedFile_2c.txt', WebDriverBy::cssSelector('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"] td.col-filename--local'));
            self::assertElementEquals($driver, '/Testcases/2c_source_folder/MovedFile_2c.txt', WebDriverBy::cssSelector('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"] td.col-filename--foreign'));
        });
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $fileSelector = WebDriverBy::cssSelector(
                '[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"]',
            );
            $fileRow = $driver->findElement($fileSelector);
            $fileRow->findElement(WebDriverBy::linkText('Publish'))->click();
        });
        TYPO3Helper::clickModalButton($localDriver, 'Publish');
        ContentPublisherHelper::waitUntilPublishingFinished($localDriver);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains(
                $driver,
                'The selected file 1:/Testcases/2c_target_folder/MovedFile_2c.txt has been published to the foreign system.',
            );
            self::assertElementContains($driver, 'Unchanged', WebDriverBy::cssSelector('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"] td.col-state .rounded-pill'));
            self::assertElementEquals($driver, 'MovedFile_2c.txt', WebDriverBy::cssSelector('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"] td.col-filename--local'));
            self::assertElementEquals($driver, 'MovedFile_2c.txt', WebDriverBy::cssSelector('[data-id="1:/Testcases/2c_target_folder/MovedFile_2c.txt"] td.col-filename--foreign'));
        });

        $localDriver->close();
        unset($localDriver);

        // Assert file on foreign
        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2c_target_folder']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::cssSelector('[data-filelist-identifier="1:/Testcases/2c_target_folder/MovedFile_2c.txt"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }

    /**
     * Test Case 2d
     */
    public function testDeletedFileCanBePublished(): void
    {
        // Assert file on foreign
        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2d_deleted_file']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::cssSelector('[data-filelist-identifier="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);

        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Files');
        TYPO3Helper::selectInFileStorageTree($localDriver, ['fileadmin', 'Testcases', '2d_deleted_file']);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - Publish files');
            self::assertElementContains($driver, 'Deleted', WebDriverBy::cssSelector('[data-id="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"] td.col-state .rounded-pill'));
            self::assertElementEquals($driver, '2d_deleted_file.txt', WebDriverBy::cssSelector('[data-id="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"] td.col-filename--local'));
            self::assertElementEquals($driver, '2d_deleted_file.txt', WebDriverBy::cssSelector('[data-id="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"] td.col-filename--foreign'));
        });
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $fileSelector = WebDriverBy::cssSelector(
                '[data-id="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]',
            );
            $fileRow = $driver->findElement($fileSelector);
            $fileRow->findElement(WebDriverBy::linkText('Publish'))->click();
        });
        TYPO3Helper::clickModalButton($localDriver, 'Publish');
        ContentPublisherHelper::waitUntilPublishingFinished($localDriver);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $localDriver): void {
            self::assertPageContains(
                $localDriver,
                'The selected file 1:/Testcases/2d_deleted_file/2d_deleted_file.txt has been published to the foreign system.',
            );
            self::assertElementNotExists($localDriver, WebDriverBy::cssSelector('[data-id="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]'));
        });

        $localDriver->close();
        unset($localDriver);

        // Assert file on foreign
        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2d_deleted_file']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementNotExists(
                $driver,
                WebDriverBy::cssSelector('[data-filelist-identifier="1:/Testcases/2d_deleted_file/2d_deleted_file.txt"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }

    /**
     * Test Case 2f
     */
    public function testDeletedFolderCanBePublished(): void
    {
        // Assert folder exists on foreign
        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2f_delete_folder']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::cssSelector('[data-filelist-identifier="1:/Testcases/2f_delete_folder/DeletedFile_2f.txt"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);

        // Assert folder is deleted and publish it
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Files');
        TYPO3Helper::selectInFileStorageTree($localDriver, ['fileadmin', 'Testcases']);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - Publish files');
            self::assertElementContains($driver, 'Deleted', WebDriverBy::cssSelector('[data-id="1:/Testcases/2g_moved_folder_with_file/"] td.col-state .rounded-pill'));
            self::assertElementEquals($driver, '---', WebDriverBy::cssSelector('[data-id="1:/Testcases/2f_delete_folder/"] td.col-filename--local'));
            self::assertElementEquals($driver, '2f_delete_folder', WebDriverBy::cssSelector('[data-id="1:/Testcases/2f_delete_folder/"] td.col-filename--foreign'));
        });
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $fileSelector = WebDriverBy::cssSelector(
                '[data-id="1:/Testcases/2f_delete_folder/"]',
            );
            $fileRow = $driver->findElement($fileSelector);
            $fileRow->findElement(WebDriverBy::linkText('Publish'))->click();
        });
        TYPO3Helper::clickModalButton($localDriver, 'Publish');
        ContentPublisherHelper::waitUntilPublishingFinished($localDriver);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains(
                $driver,
                'The selected folder 1:/Testcases/2f_delete_folder/ has been published to the foreign system.',
            );
            self::assertElementNotExists($driver, WebDriverBy::cssSelector('[data-id="1:/Testcases/2f_delete_folder/"]'));
        });

        $localDriver->close();
        unset($localDriver);

        // Assert file on foreign
        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementNotExists(
                $driver,
                WebDriverBy::cssSelector('[data-id="1:/Testcases/2f_delete_folder/"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }

    /**
     *  Test Case 2g
     */
    public function testRenamedFolderCanBePublished(): void
    {
        // Assert folder exists on foreign
        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2g_moved_folder_with_file']);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::cssSelector('[data-filelist-identifier="1:/Testcases/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);

        // Assert folder is deleted and publish it
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Files');
        TYPO3Helper::selectInFileStorageTree($localDriver, ['fileadmin', 'Testcases', '2g_target_folder', '2g_moved_folder_with_file']);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - Publish files');
            self::assertElementContains($driver, 'Moved', WebDriverBy::cssSelector('[data-id="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"] td.col-state .rounded-pill'));
            self::assertElementEquals($driver, '/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt', WebDriverBy::cssSelector('[data-id="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"] td.col-filename--local'));
            self::assertElementEquals($driver, '/Testcases/2g_moved_folder_with_file/MovedFileInFolder_2g.txt', WebDriverBy::cssSelector('[data-id="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"] td.col-filename--foreign'));
        });
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $fileSelector = WebDriverBy::cssSelector(
                '[data-id="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]',
            );
            $fileRow = $driver->findElement($fileSelector);
            $fileRow->findElement(WebDriverBy::linkText('Publish'))->click();
        });
        TYPO3Helper::clickModalButton($localDriver, 'Publish');
        ContentPublisherHelper::waitUntilPublishingFinished($localDriver);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains(
                $driver,
                'The selected file 1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt has been published to the foreign system.',
            );
            self::assertElementContains($driver, 'Unchanged', WebDriverBy::cssSelector('[data-id="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"] td.col-state .rounded-pill'));
        });

        $localDriver->close();
        unset($localDriver);

        // Assert file on foreign
        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Filelist');
        TYPO3Helper::selectInFileStorageTree($foreignDriver, ['fileadmin', 'Testcases', '2g_target_folder', '2g_moved_folder_with_file']);

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertElementExists(
                $driver,
                WebDriverBy::cssSelector('[data-filelist-identifier="1:/Testcases/2g_target_folder/2g_moved_folder_with_file/MovedFileInFolder_2g.txt"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }
}
