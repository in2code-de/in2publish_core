<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\Elements\Select;
use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class RecordTreeDisplayTest extends AbstractBrowserTestCase
{
    public function testTheLevelOfRecordsToShowCanBeSelected(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        TYPO3Helper::selectInPageTree($localDriver, []);
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('0 level');

            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageNotContains($driver, 'Home');
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageNotContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageNotContains($driver, '4.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 5');
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('1 level');

            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageNotContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageNotContains($driver, '4.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 5');
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('2 levels');

            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageNotContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageNotContains($driver, '4.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 5');
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('3 levels');

            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageNotContains($driver, '4.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 5');
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('4 levels');

            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageNotContains($driver, '4.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 5');
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('5 levels');

            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageNotContains($driver, '4.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 5');
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('6 levels');

            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageContains($driver, '4.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 5');
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('7 levels');

            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageContains($driver, '4.1.1.1 Subpage - Level 4');
            self::assertPageContains($driver, '4.1.1.1.1 Subpage - Level 5');
        });

        $localDriver->close();
        unset($localDriver);
    }

    public function testRecordTreeStartsWithSelectedPage(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('1 level');
        });

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageNotContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
        });

        TYPO3Helper::selectInPageTree($localDriver, ['Home']);

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageNotContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageNotContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
        });

        TYPO3Helper::selectInPageTree($localDriver, ['Home', 'EXT:in2publish_core']);

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageNotContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageNotContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
        });

        TYPO3Helper::selectInPageTree($localDriver, ['Home', 'EXT:in2publish_core', '4 PageTree depth']);

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageNotContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageNotContains($driver, 'Home');
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
        });

        $localDriver->close();
        unset($localDriver);
    }
}
