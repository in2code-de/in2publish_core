<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\Elements\Single\Select;
use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\Factory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class RecordTreeDisplayTest extends AbstractBrowserTestCase
{
    public function testTheLevelOfRecordsToShowCanBeSelected(): void
    {
        $driver = Factory::getInstance()->createMultiDriver('local');
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($driver, 'Publish Overview');

        TYPO3Helper::selectInPageTree($driver, []);
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
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

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
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

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
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

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
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

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
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

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
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

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
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

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
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
    }

    public function testRecordTreeStartsWithSelectedPage(): void
    {
        $driver = Factory::getInstance()->createMultiDriver('local');
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($driver, 'Publish Overview');

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::name('depth')));
            $select->setValueByText('1 level');

            self::assertPageContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageNotContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
        });

        TYPO3Helper::selectInPageTree($driver, ['Home']);

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageNotContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageNotContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
        });

        TYPO3Helper::selectInPageTree($driver, ['Home', 'EXT:in2publish_core']);

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageNotContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageNotContains($driver, 'Home');
            self::assertPageContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
        });

        TYPO3Helper::selectInPageTree($driver, ['Home', 'EXT:in2publish_core', '4 PageTree depth']);

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageNotContains($driver, '[LOCAL] CP TYPO3 v12');
            self::assertPageNotContains($driver, 'Home');
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
        });
    }
}
