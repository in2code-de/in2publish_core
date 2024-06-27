<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;

class PublishOverviewModuleTest extends AbstractBrowserTestCase
{
    public function testPublishOverviewModuleCanBeOpened(): void
    {
        $driver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');

        TYPO3Helper::selectModuleByText($driver, 'Page');
        TYPO3Helper::selectInPageTree($driver, ['Home']);
        TYPO3Helper::selectModuleByText($driver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 'TYPO3 Content Publisher - publish pages and records overview');
        });

        $driver->close();
    }
}
