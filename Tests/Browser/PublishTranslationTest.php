<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PublishTranslationTest extends AbstractBrowserTestCase
{
    public function testTranslatedContentInFreeModeCanBePublished(): void
    {
        $driver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($driver, 'Page');
        TYPO3Helper::selectInPageTree(
            $driver,
            ['Home', 'EXT:in2publish_core', '1d Translated Content', '1d.1 Free Mode'],
        );
        TYPO3Helper::selectModuleByText($driver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-72"]'));

            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-72"]'));
            $classes = $recordRow->getAttribute('class');
            self::assertContains('in2publish-stagelisting__item--changed', GeneralUtility::trimExplode(' ', $classes));

            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-72"]'));
            $info = $recordRow->findElement(
                WebDriverBy::cssSelector('[data-action="opendirtypropertieslistcontainer"]'),
            );
            $info->click();
            self::assertElementContains(
                $driver,
                'Header in German - Version 3',
                WebDriverBy::cssSelector('.in2publish-stagelisting__dropdown__item--left'),
            );
            self::assertElementContains(
                $driver,
                'Header in German - Version 2',
                WebDriverBy::cssSelector('.in2publish-stagelisting__dropdown__item--right'),
            );
        });

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            $driver->findElement(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-72"]//*[@class="in2publish-icon-publish"]',
                ),
            )->click();
        });
        sleep($this->sleepTime);
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });

        TYPO3Helper::selectModuleByText($driver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-72"]'));

            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-72"]'));
            $classes = $recordRow->getAttribute('class');
            self::assertContains(
                'in2publish-stagelisting__item--unchanged',
                GeneralUtility::trimExplode(' ', $classes),
            );
        });

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::selectInPageTree(
            $foreignDriver,
            ['Home', 'EXT:in2publish_core', '1d Translated Content', '1d.1 Free Mode'],
        );
        // Workaround
        sleep(1);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'Header in German - Version 3');
        });
        $foreignDriver->close();
        $driver->close();

        unset($foreignDriver);
        unset($driver);

        self::assertTrue(true);
    }

    public function testTranslatedContentInConnectedModeCanBePublished(): void
    {
        $driver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($driver, 'Page');
        TYPO3Helper::selectInPageTree(
            $driver,
            ['Home', 'EXT:in2publish_core', '1d Translated Content', '1d.2 Connected Mode'],
        );
        TYPO3Helper::selectModuleByText($driver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-75"]'));

            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-75"]'));
            $classes = $recordRow->getAttribute('class');
            self::assertContains('in2publish-stagelisting__item--changed', GeneralUtility::trimExplode(' ', $classes));

            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-75"]'));
            $info = $recordRow->findElement(
                WebDriverBy::cssSelector('[data-action="opendirtypropertieslistcontainer"]'),
            );
            $info->click();
            self::assertElementContains(
                $driver,
                'Header in German - Version 3',
                WebDriverBy::cssSelector('.in2publish-stagelisting__dropdown__item--left'),
            );
            self::assertElementContains(
                $driver,
                'Header in German - Version 2',
                WebDriverBy::cssSelector('.in2publish-stagelisting__dropdown__item--right'),
            );
        });

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            $driver->findElement(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-75"]//*[@class="in2publish-icon-publish"]',
                ),
            )->click();
        });

        // Workaround
        sleep(2);

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });

        TYPO3Helper::selectModuleByText($driver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-75"]'));

            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-75"]'));
            $classes = $recordRow->getAttribute('class');
            self::assertContains(
                'in2publish-stagelisting__item--unchanged',
                GeneralUtility::trimExplode(' ', $classes),
            );
        });

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::selectInPageTree(
            $foreignDriver,
            ['Home', 'EXT:in2publish_core', '1d Translated Content', '1d.2 Connected Mode'],
        );
        // Workaround
        sleep(2);
        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'Header in German - Version 3');
        });
        $foreignDriver->close();
        $driver->close();

        unset($foreignDriver);
        unset($driver);

        self::assertTrue(true);
    }
}
