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
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($localDriver, 'Publisher Tools');

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->click(WebDriverBy::linkText('Tests'));
        });

        // Workaround
        sleep($this->sleepTime);

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertSourceNotContains($driver, 'callout-warning');
            self::assertSourceNotContains($driver, 'callout-danger');
        });

        $localDriver->close();
        unset($localDriver);
    }
}
