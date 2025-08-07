<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class PublishChangedContentTest extends AbstractBrowserTestCase
{
    /**
     * Test Case 1b
     */
    public function testChangedPageContentCanBePublished(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($localDriver, 'Page');
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($localDriver, '1b.1 Page content - changed');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-65"]'));
            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-65"]'));
            $info = $recordRow->findElement(
                WebDriverBy::cssSelector('[data-action="opendirtypropertieslistcontainer"]'),
            );
            $info->click();
        });

        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, '1b.1 Header - changed');
            $driver->click(WebDriverBy::cssSelector('.icon-actions-arrow-right'));
        });

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v13.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Page');
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($foreignDriver, '1b.1 Page content - changed');

        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            $driver->findElement(
                WebDriverBy::xpath(
                    '//div[contains(@class, "t3-page-ce") and @data-table="tt_content" and @data-uid="49"]' .
                    '//div[contains(@class, "btn-group")]//a[@title="Edit"]'
                )
            )->click();

            self::assertElementContains(
                $driver,
                '1b.1 Header - changed',
                WebDriverBy::cssSelector('[data-formengine-input-name="data[tt_content][49][header]"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }
}
