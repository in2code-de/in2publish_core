<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class PublishChangedPagePropertiesTest extends AbstractBrowserTestCase
{
    /**
     * Test Case 1a
     */
    public function testChangedPagePropertiesCanBePublished(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($localDriver, 'Page');
        TYPO3Helper::selectInPageTree($localDriver, ['Home', 'EXT:in2publish_core', '1a Page properties - changed']);
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-5"]'));
            $recordRow = $driver->findElement(WebDriverBy::cssSelector('[data-record-identifier="pages-5"]'));
            $info = $recordRow->findElement(
                WebDriverBy::cssSelector('[data-action="opendirtypropertieslistcontainer"]'),
            );
            $info->click();
            self::assertPageContains($driver, 'Page title 1a Page properties - changed');
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->click(WebDriverBy::cssSelector('.in2publish-icon-publish'));
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Page');
        TYPO3Helper::selectInPageTree($foreignDriver, ['Home', 'EXT:in2publish_core', '1a Page properties - changed']);

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            $driver->findElement(WebDriverBy::xpath('//a[@title="Edit"]'))->click();
            self::assertElementContains(
                $driver,
                '1a Page properties - changed',
                WebDriverBy::cssSelector('[data-formengine-input-name="data[pages][5][title]"]'),
            );
        });

        $foreignDriver->close();
        unset($foreignDriver);
    }
}
