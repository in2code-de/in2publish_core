<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Helper;

use CoStack\StackTest\Test\Constraint\Visibility\ElementIsNotVisible;
use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class ContentPublisherHelper
{
    public static function waitUntilPublishingFinished(WebDriver $driver): void
    {
        if ($driver->isInIFrameContext()) {
            $driver->wait()->until(ElementIsNotVisible::resolve(WebDriverBy::cssSelector('.in2publish-loading-overlay')));
            return;
        }
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver):void {
            $driver->wait()->until(ElementIsNotVisible::resolve(WebDriverBy::cssSelector('.in2publish-loading-overlay')));
        });
    }
}
