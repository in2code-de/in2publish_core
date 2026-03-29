<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser_legacy\Dependency;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use In2code\In2publishCore\Tests\Browser_legacy\AbstractBrowserTestCase;

/**
 * @ticket https://projekte.in2code.de/issues/52878
 */
class PublishingRecordWithDependencyTest extends AbstractBrowserTestCase
{
    public function testRecordWithUnfulfilledDependencyIsPublishableAfterDependenciesAreFulfilled(): void
    {
        $localDriver = WebDriverFactory::createChromeDriver();
        TYPO3Helper::backendLogin(
            $localDriver,
            'https://local.v13.in2publish-core.de/typo3',
            'publisher-page-tree-publish',
            'publisher-page-tree-publish',
        );
        TYPO3Helper::selectModuleByText($localDriver, 'Page');
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($localDriver, '5c.1 Parent not published');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, '5c.1 Parent not published');
            self::assertPageContains($driver, '5c.1.1 Child Ready to Publish');

            self::assertElementIsVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-35"]//*[contains(@class, "icon-actions-arrow-right")]',
                ),
            );

            // Not publishable exclamation triangle
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[contains(@class, "icon-actions-exclamation-triangle-alt")]',
                ),
            );

            $driver->click(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[@data-action="opendirtypropertieslistcontainer"]',
                ),
            );
            self::assertElementContains(
                $driver,
                'The page "5c.1 Parent not published" must be published first.',
                WebDriverBy::xpath('//*[@data-record-identifier="pages-36"]'),
            );
            self::assertElementContains(
                $driver,
                'Affected records: "Header on not published Parent page 5c.1", "5c.1.1 Child Ready to Publish"',
                WebDriverBy::xpath('//*[@data-record-identifier="pages-36"]'),
            );
            self::assertElementContains(
                $driver,
                '"Insert Record on Child Ready to Publish 5c.1.1" requires that the page "5c.1.1 Child Ready to Publish" is published first.',
                WebDriverBy::xpath('//*[@data-record-identifier="pages-36"]'),
            );
            self::assertElementContains(
                $driver,
                'The record "Header on not published Parent page 5c.1" is a target of the shortcut record "Insert Record on Child Ready to Publish 5c.1.1". The target must be published before the shortcut record can be published.',
                WebDriverBy::xpath('//*[@data-record-identifier="pages-36"]'),
            );
        });

        // Publish the parent page
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->click(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-35"]//*[contains(@class, "icon-actions-arrow-right")]',
                ),
            );
        });

        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, '5c.1 Parent not published');
            self::assertPageContains($driver, '5c.1.1 Child Ready to Publish');

            // Not publishable exclamation triangle is gone and child is publishable
            self::assertElementIsNotVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[contains(@class, "icon-actions-exclamation-triangle-alt")]',
                ),
            );
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[contains(@class, "icon-actions-arrow-right")]',
                ),
            );
        });

        $localDriver->close();
        unset($localDriver);
    }
}
