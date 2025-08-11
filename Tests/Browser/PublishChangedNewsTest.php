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
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($localDriver, 'Page');
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($localDriver, 'News Folder');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
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
                WebDriverBy::cssSelector('.in2publish-page__content'),
            );


            self::assertElementContains(
                $driver,
                '1:/user_upload/roman-wimmers-STrq0wSBGIs-unsplash.jpg',
                WebDriverBy::cssSelector('.in2publish-page__content')
            );
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->click(WebDriverBy::cssSelector('.icon-actions-arrow-right'));
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($foreignDriver, 'News Folder');

        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'Content element with image - edited');
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }
}
