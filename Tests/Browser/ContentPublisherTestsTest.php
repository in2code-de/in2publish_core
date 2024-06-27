<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class ContentPublisherTestsTest extends AbstractBrowserTestCase
{
    public function testTestsAreGreen(): void
    {
        $driver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($driver, 'Publisher Tools');

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            $driver->click(WebDriverBy::linkText('Tests'));
            self::assertSourceNotContains($driver, 'callout-warning');
            self::assertSourceNotContains($driver, 'callout-danger');
        });

        $driver->close();

        self::assertTrue(true);
    }
}
