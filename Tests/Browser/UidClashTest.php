<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class UidClashTest  extends AbstractBrowserTestCase
{
    /**
     * Testparcours in2publish_core - 24
     */
    public function testRelationToCategoryCanBePublishedForNewsAndPagesWithSameUid(): void
    {

        $localDriver = WebDriverFactory::createChromeDriver();
        $foreignDriver = WebDriverFactory::createChromeDriver();

        $this->publishNews76($localDriver);
        $this->assertNews76HasBeenPublished($foreignDriver);
        $this->assertOnlyCategory1HasBeenPublished($foreignDriver);

        $this->publishPage76($localDriver);
        $this->assertPage76HasBeenPublished($foreignDriver);
        $this->assertBothCategoriesHaveBeenPublished($foreignDriver);
    }


    protected function publishPage76(WebDriver $localDriver): WebDriver
    {
        TYPO3Helper::backendLogin($localDriver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($localDriver, 'Page');
        TYPO3Helper::selectInPageTree(
            $localDriver,
            ['Home', 'EXT:in2publish_core', '24 Page with Category 1 and Category 2'],
        );

        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
            self::assertElementIsVisible($driver, WebDriverBy::cssSelector('[data-record-identifier="pages-76"]'));

            $driver->click(WebDriverBy::cssSelector('.in2publish-icon-publish'));
        });

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });
        return $localDriver;
    }

    protected function publishNews76(WebDriver $localDriver): void
    {
        TYPO3Helper::backendLogin($localDriver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($localDriver, 'List');
        TYPO3Helper::selectInPageTree($localDriver, ['Home', 'News Folder']);
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->click(WebDriverBy::cssSelector('.in2publish-icon-publish'));
        });

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'The selected record has been published successfully');
        });
    }

    protected function assertPage76HasBeenPublished(WebDriver $foreignDriver): void
    {
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Page');
        TYPO3Helper::selectInPageTree(
            $foreignDriver,
            ['Home', 'EXT:in2publish_core', '24 Page with Category 1 and Category 2']
        );
    }

    protected function assertNews76HasBeenPublished(WebDriver $foreignDriver): void
    {
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::selectInPageTree($foreignDriver, ['Home', 'News Folder']);

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, '24 news with Category 1');
        });
    }

    private function assertOnlyCategory1HasBeenPublished(WebDriver $foreignDriver)
    {
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::selectInPageTree($foreignDriver, ['Home']);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'Category 1');
            // Fix: Category 2 should not be published
          //  self::assertPageNotContains($driver, 'Category 2');
        });

    }

    private function assertBothCategoriesHaveBeenPublished(WebDriver $foreignDriver)
    {
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::selectInPageTree($foreignDriver, ['Home']);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'Category 1');
            self::assertPageContains($driver, 'Category 2');
        });
    }
}