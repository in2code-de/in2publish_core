<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser\Regression;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use In2code\In2publishCore\Tests\Browser\AbstractBrowserTestCase;

class BackendUserPreferencesResetTest extends AbstractBrowserTestCase
{
    public function testBackendUserSettingsCanBeReset(): void
    {
        $driver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');

        $driver->click(WebDriverBy::cssSelector('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem'));
        $driver->click(WebDriverBy::linkText('User Settings'));

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            $driver->click(WebDriverBy::linkText('Reset configuration'));
            $driver->click(WebDriverBy::cssSelector('[data-event-payload="resetConfiguration"]'));
        });

        TYPO3Helper::clickModalButton($driver, 'OK');

        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageContains(
                $driver,
                'The user settings have been reset to default values and temporary data has been cleared.',
            );
        });

        $driver->close();
    }
}
