<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

/**
 * Testparcours in2publish_core - 24
 */
class UidClashTest  extends AbstractBrowserTestCase
{
    /**
     * Use Case 1
     *
     * Page 24 only is published in Overview Module
     * Result as expected: Page is published, both categories are published, only the 2 mm-Records for page/category are published
     */
    public function testUseCase1(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        $this->publishPage76($localDriver);

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        $this->assertPage76HasBeenPublished($foreignDriver);
        $this->assertBothCategoriesHaveBeenPublished($foreignDriver);

        $foreignDriver->close();
        unset($foreignDriver);
    }

    /**
     * Use Case 2
     *
     * News folder with News 24 is published in Overview Module
     * Result as expected: News 24 is published, Page 24 is not published, only Category 1 is published, only the news mm-Records is published
     */
    public function testUseCase2(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        $this->publishNews76($localDriver);

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();
        $this->assertNews76HasBeenPublished($foreignDriver);
        $this->assertOnlyCategory1HasBeenPublished($foreignDriver);

        $foreignDriver->close();
        unset($foreignDriver);

    }

    /**
     * Use Case 3
     *
     * Page 24 is published first, then News 24
     * Result as expected: first only the page, categories and 2 page mm-records are published, then the news and the news mm-record
     */
    public function testUseCase3(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();

        $this->publishPage76($localDriver);
        $this->publishNews76($localDriver);

        $localDriver->close();
        unset($localDriver);

        $foreignDriver = WebDriverFactory::createChromeDriver();

        $this->assertPage76HasBeenPublished($foreignDriver);
        $this->assertNews76HasBeenPublished($foreignDriver);
        $this->assertBothCategoriesHaveBeenPublished($foreignDriver);


        $foreignDriver->close();
        unset($foreignDriver);
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
            self::assertPageNotContains($driver, 'Category 2');
        });

    }

    private function assertBothCategoriesHaveBeenPublished(WebDriver $foreignDriver)
    {
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'List');
        TYPO3Helper::selectInPageTree($foreignDriver, ['Home']);

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($foreignDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'Category 1');
            self::assertPageContains($driver, 'Category 2');
        });
    }
}