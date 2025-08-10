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
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($localDriver, 'Page');
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($localDriver, '1d.1 Free Mode');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
            self::assertElementExists($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-72"] .in2publish-badge--changed'));
            self::assertElementExists($driver, WebDriverBy::xpath('//*[@data-record-identifier="pages-72"]//*[contains(@class, "icon-actions-arrow-right")]'));

            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-72"]'));
            $infoAction = $recordRow->findElement(
                WebDriverBy::cssSelector('[data-action="opendirtypropertieslistcontainer"]'),
            );
            $infoAction->click();
            self::assertElementContains(
                $driver,
                'Header in German - Version 3',
                WebDriverBy::cssSelector('.in2publish-dirty-properties-local'),
            );
            self::assertElementContains(
                $driver,
                'Header in German - Version 2',
                WebDriverBy::cssSelector('.in2publish-dirty-properties-foreign'),
            );
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->findElement(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-72"]//*[contains(@class, "icon-actions-arrow-right")]',
                ),
            )->click();
        });

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });

        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-72"]'));

            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-72"]'));
            $classes = $recordRow->getAttribute('class');
            self::assertElementExists($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-72"] .in2publish-badge--unchanged'));
        });

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($foreignDriver, '1d.1 Free Mode');

        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'Header in German - Version 3');
        });
        $foreignDriver->close();
        unset($foreignDriver);
    }

    public function testTranslatedContentInConnectedModeCanBePublished(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($localDriver, 'Page');
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($localDriver, '1d.2 Connected Mode');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-75"]'));

            $statusBadge = $driver->findElement(
                WebDriverBy::cssSelector('[data-record-identifier="pages-75"] .in2publish-badge--changed')
            );
            self::assertTrue($statusBadge->isDisplayed(), 'Changed badge should be visible');

            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-75"]'));
            $info = $recordRow->findElement(
                WebDriverBy::cssSelector('[data-action="opendirtypropertieslistcontainer"]'),
            );
            $info->click();
            self::assertElementContains(
                $driver,
                'Header in German - Version 3',
                WebDriverBy::cssSelector('.in2publish-dirty-properties-local'),
            );
            self::assertElementContains(
                $driver,
                'Header in German - Version 2',
                WebDriverBy::cssSelector('.in2publish-dirty-properties-foreign'),
            );
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->findElement(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-75"]//*[contains(@class, "icon-actions-arrow-right")]',
                ),
            )->click();
        });

        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });

        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertElementExists($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-75"] .in2publish-badge--unchanged'));
        });

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($foreignDriver, '1d.2 Connected Mode');

        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'Header in German - Version 3');
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }
}
