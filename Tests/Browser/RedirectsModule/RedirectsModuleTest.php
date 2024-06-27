<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser\RedirectsModule;

use CoStack\StackTest\Elements\FormElementFactory;
use CoStack\StackTest\Elements\Select;
use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use In2code\In2publishCore\Tests\Browser\AbstractBrowserTestCase;

/**
 * @ticket https://projekte.in2code.de/issues/52878
 */
class RedirectsModuleTest extends AbstractBrowserTestCase
{
    public function testRedirectWithoutAssociationCanBePublished(): void
    {
        $driver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin($driver, 'https://local.v12.in2publish-core.de/typo3', 'admin', 'password');
        TYPO3Helper::selectModuleByText($driver, 'Publish Redirects');
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, 't3://page?uid=67&_language=0');
            self::assertPageContains($driver, 't3://page?uid=39&_language=0');
            self::assertPageContains($driver, '/extin2publish/8-treatremovedanddeletedasdifference');
            self::assertElementIsVisible($driver, WebDriverBy::xpath('//a[@title="Publish with site association"]'));
            $driver->click(WebDriverBy::xpath('//a[@title="Publish with site association"]'));
        });
        TYPO3Helper::inContentIFrameContext($driver, static function (WebDriver $driver): void {
            $element = $driver->findElement(WebDriverBy::name('properties[siteId]'));
            $select = new Select($element);
            $select->setValue('main');
            $driver->click(WebDriverBy::name('_saveandpublish'));

            self::assertPageContains($driver, 'Associated redirect Redirect [19] (local.v12.in2publish.de) /extin2publish/8-treatremovedanddeletedasdifference -> t3://page?uid=39&_language=0 with site main');
        });
        $driver->close();
    }
}
