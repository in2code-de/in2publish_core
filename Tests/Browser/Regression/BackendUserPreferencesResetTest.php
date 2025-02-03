<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser\Regression;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use In2code\In2publishCore\Tests\Browser\AbstractBrowserTestCase;

class BackendUserPreferencesResetTest extends AbstractBrowserTestCase
{
    public function testBackendUserSettingsCanBeReset(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($localDriver, 'https://local.v13.in2publish-core.de/typo3', 'admin', 'password');

        $localDriver->click(WebDriverBy::cssSelector('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem'));
        $localDriver->click(WebDriverBy::linkText('User Settings'));

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->click(WebDriverBy::xpath('//button[contains(text(), "Reset configuration")]'));


            $driver->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(
                    WebDriverBy::cssSelector('input[data-event-payload="resetConfiguration"]')
                )
            );
            $driver->click(WebDriverBy::cssSelector('input[data-event-payload="resetConfiguration"]'));
        });

        TYPO3Helper::clickModalButton($localDriver, 'OK');

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains(
                $driver,
                'The user settings have been reset to default values and temporary data has been cleared.',
            );
        });

        $localDriver->close();
        unset($localDriver);
    }
}
