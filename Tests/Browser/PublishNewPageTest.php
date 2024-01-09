<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\Test\Constraint\Content\PageContains;
use CoStack\StackTest\Test\Constraint\Visibility\ElementIsVisible;
use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\Factory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class PublishNewPageTest extends AbstractBrowserTestCase
{
    public function testNewPageCanBeCreatedAndPublished(): void
    {
        $driver = Factory::getInstance()->createMultiDriver('local');
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($driver, 'List');
        TYPO3Helper::selectInPageTree($driver, ['Home', 'EXT:in2publish_core', '1b Page content']);

        $driver->inFirstDriver(static function (WebDriver $driver): void {
            TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
                $driver->click('Create new record');
                $driver->click('Page (inside)');

                TYPO3Helper::fillTYPO3FormField($driver, 'Page Title', 'Foo Bar Baz');
                $driver->click(WebDriverBy::name('_savedok'));
            });
            $driver->wait()->until(ElementIsVisible::resolve(WebDriverBy::cssSelector('button.close')));
            $driver->click(WebDriverBy::cssSelector('button.close'));
        });

        TYPO3Helper::refreshPageTree($driver);
        TYPO3Helper::selectModuleByText($driver, 'Page');
        TYPO3Helper::selectInPageTree($driver, ['Home', 'EXT:in2publish_core', '1b Page content', 'Foo Bar Baz']);

        $driver->inFirstDriver(static function (WebDriver $driver): void {
            TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
                $driver->click('Draft');
                $driver->click(WebDriverBy::className('in2publish__workflowstatelabel--1'));
                $driver->submit(WebDriverBy::tagName('form'));

                $publishButtonIconSelector = WebDriverBy::cssSelector(
                    '[title="Publish this record now"] [data-identifier="actions-move-right"]'
                );
                $driver->wait()->until(ElementIsVisible::resolve($publishButtonIconSelector));
                $driver->click(WebDriverBy::cssSelector('[title="Publish this record now"]'));
                $driver->wait()->until(PageContains::resolve('Successfully published'));
            });
        });
        TYPO3Helper::waitUntilContentIFrameIsLoaded($driver);
        TYPO3Helper::refreshPageTree($driver);

        $foreignDriver = Factory::getInstance()->createMultiDriver('foreign');
        TYPO3Helper::backendLogin($foreignDriver, 'https://foreign.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($foreignDriver, 'Page');
        TYPO3Helper::selectInPageTree(
            $foreignDriver,
            ['Home', 'EXT:in2publish_core', '1b Page content', 'Foo Bar Baz'],
        );
        $foreignDriver->close();

        self::assertTrue(true);
    }
}
