<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class PublishChangedNewsTest extends AbstractBrowserTestCase
{
    /**
     * Testparcours in2publish_core - 1c
     */
    public function testChangedPageContentCanBePublished(): void
    {
        $driver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($driver, 'Page');
        TYPO3Helper::selectInPageTree($driver, ['Home', 'News Folder']);
        TYPO3Helper::selectModuleByText($driver, 'Publish Overview');

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-33"]'));
            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-33"]'));
            $info = $recordRow->findElement(
                WebDriverBy::cssSelector('[data-action="opendirtypropertieslistcontainer"]'),
            );
            $info->click();
            self::assertElementContains(
                $driver,
                'Content element with image - edited',
                WebDriverBy::cssSelector('.in2publish-stagelisting__dropdown__item--left'),
            );
            self::assertElementContains(
                $driver,
                'Content element with image',
                WebDriverBy::cssSelector('.in2publish-stagelisting__dropdown__item--right'),
            );
            $relatedRecordsList = WebDriverBy::cssSelector('.in2publish-related__list');
            self::assertElementContains(
                $driver,
                '1:/user_upload/roman-wimmers-STrq0wSBGIs-unsplash.jpg',
                $relatedRecordsList
            );
        });

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            $driver->click(WebDriverBy::cssSelector('.in2publish-icon-publish'));
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::selectInPageTree($foreignDriver, ['Home', 'News Folder']);
        // Workaround
        sleep(1);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'Content element with image - edited');
        });
        $foreignDriver->close();
        $driver->close();

        self::assertTrue(true);
    }
}
