<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\Test\Constraint\Visibility\ElementIsVisible;
use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class PublishTextpicTest extends AbstractBrowserTestCase
{
    /**
     * Test Case 1e
     */
    public function testTextPicCanBePublished(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($localDriver, 'Page');
        TYPO3Helper::selectInPageTree($localDriver, ['Home', 'EXT:in2publish_core', '1e Page with textpic']);
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-79"]'));
            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-79"]'));
            $info = $recordRow->findElement(
                WebDriverBy::cssSelector('[data-action="opendirtypropertieslistcontainer"]'),
            );
            $info->click();
            self::assertPageContains($driver, '1e Page with textpic');
            // Relation to sys_file is resolved
            self::assertPageContains($driver, 'pages [79] / sys_file_reference [11] / sys_file [5] / _file [1:/user_upload/maxim-berg-9XunOfueKKI-unsplash.jpg]');
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->click(WebDriverBy::cssSelector('.icon-actions-arrow-right'));
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Page');
        TYPO3Helper::selectInPageTree($foreignDriver, ['Home', 'EXT:in2publish_core', '1e Page with textpic']);

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            // Assert the textpic content element exists and contains the image
            $previewElement = $driver->findElement(WebDriverBy::cssSelector('.preview-thumbnails-element'));
            self::assertNotNull($previewElement, 'Preview element should exist');

            // Find and verify the image
            $image = $previewElement->findElement(WebDriverBy::cssSelector('img[alt="maxim-berg-9XunOfueKKI-unsplash.jpg"]'));
            self::assertNotNull($image, 'Image should be present');
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }
}
