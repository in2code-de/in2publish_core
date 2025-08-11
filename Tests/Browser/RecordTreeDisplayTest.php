<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\Elements\Select;
use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

use function Symfony\Component\String\s;

class RecordTreeDisplayTest extends AbstractBrowserTestCase
{
    public function testTheLevelOfRecordsToShowCanBeSelected(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($localDriver, 'Page');

        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($localDriver, '4 PageTree depth');

        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        sleep($this->sleepTime);

        // 0 levels
/*        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::id('in2publish__publishfilter_level')));
            $select->setValueByText('0 levels');
            $driver->wait(2);
            self::assertPageNotContains($driver, 'Home');
            self::assertPageContains($driver, '4 PageTree depth');
            //TODO
            self::assertPageNotContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageNotContains($driver, '4.1.1.1 Subpage - Level 3');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1.1 Subpage - Level 5');
        });*/

        // 1 level
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::id('in2publish__publishfilter_level')));
            $select->setValueByText('1 level');
            $driver->wait(2);
            self::assertPageNotContains($driver, 'Home');
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageNotContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageNotContains($driver, '4.1.1.1 Subpage - Level 3');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1.1 Subpage - Level 5');
        });

        // 2 levels
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::id('in2publish__publishfilter_level')));
            $select->setValueByText('2 levels');
            $driver->wait(2);
            self::assertPageNotContains($driver, 'Home');
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageNotContains($driver, '4.1.1.1 Subpage - Level 3');
            self::assertPageNotContains($driver, '4.1.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1.1 Subpage - Level 5');
        });

        // 3 levels
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::id('in2publish__publishfilter_level')));
            $select->setValueByText('3 levels');
            $driver->wait(2);
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageContains($driver, '4.1.1.1 Subpage - Level 3');
            self::assertPageNotContains($driver, '4.1.1.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1.1 Subpage - Level 5');
        });

        // 4 levels
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::id('in2publish__publishfilter_level')));
            $select->setValueByText('4 levels');
            $driver->wait(2);
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageContains($driver, '4.1.1.1 Subpage - Level 3');
            self::assertPageContains($driver, '4.1.1.1.1 Subpage - Level 4');
            self::assertPageNotContains($driver, '4.1.1.1.1.1 Subpage - Level 5');
        });

        // 5 levels
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $select = new Select($driver->findElement(WebDriverBy::id('in2publish__publishfilter_level')));
            $select->setValueByText('5 levels');
            $driver->wait(2);
            self::assertPageNotContains($driver, 'EXT:in2publish_core');
            self::assertPageContains($driver, '4 PageTree depth');
            self::assertPageContains($driver, '4.1 Subpage - Level 1');
            self::assertPageContains($driver, '4.1.1 Subpage - Level 2');
            self::assertPageContains($driver, '4.1.1.1 Subpage - Level 3');
            self::assertPageContains($driver, '4.1.1.1.1 Subpage - Level 4');
            self::assertPageContains($driver, '4.1.1.1.1.1 Subpage - Level 5');
        });

        $localDriver->close();
        unset($localDriver);
    }
}
