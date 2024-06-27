<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Browser\Dependency;

use CoStack\StackTest\TYPO3\TYPO3Helper;
use CoStack\StackTest\WebDriver\WebDriverFactory;
use CoStack\StackTest\WebDriver\Remote\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use In2code\In2publishCore\Tests\Browser\AbstractBrowserTestCase;

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
            'https://local.v12.in2publish-core.de/typo3',
            'publisher-page-tree-publish',
            'publisher-page-tree-publish',
        );
        TYPO3Helper::selectModuleByText($localDriver, 'Page');
        TYPO3Helper::selectInPageTree(
            $localDriver,
            ['EXT:in2publish', '5c Workflows - Unfulfilled Dependencies', '5c.1 Parent not published'],
        );
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, '5c.1 Parent not published');
            self::assertPageContains($driver, '5c.1.1 Child Ready to Publish');

            self::assertElementIsVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-35"]//*[contains(@class, "in2publish-link-publish")]/*[contains(@class, "in2publish-icon-publish")]',
                ),
            );

            // Not publishable exclamation triangle
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[contains(@class, "in2publish-link-publish")]/*[contains(@class, "icon-actions-exclamation-triangle-alt")]',
                ),
            );

            $driver->click(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[@data-action="opendirtypropertieslistcontainer"]',
                ),
            );
            self::assertElementContains(
                $driver,
                'pages [36] / tt_content [22] / tt_content [21] -> pages [35]: "Header on not published Parent page 5c.1" requires that the page "5c.1 Parent not published" is published first.',
                WebDriverBy::xpath('//*[@data-record-identifier="pages-36"]'),
            );
            self::assertElementContains(
                $driver,
                'pages [36] / tt_content [22] -> pages [36]: "Insert Record on Child Ready to Publish 5c.1.1" requires that the page "5c.1.1 Child Ready to Publish" is published first.',
                WebDriverBy::xpath('//*[@data-record-identifier="pages-36"]'),
            );
            self::assertElementContains(
                $driver,
                'pages [36] / tt_content [22] -> tt_content [21]: The record "Header on not published Parent page 5c.1" is a target of the shortcut record "Insert Record on Child Ready to Publish 5c.1.1". The target must be published before the shortcut record can be published.',
                WebDriverBy::xpath('//*[@data-record-identifier="pages-36"]'),
            );
            self::assertElementContains(
                $driver,
                'pages [36] -> pages [35]: "5c.1.1 Child Ready to Publish" requires that the page "5c.1 Parent not published" is published first.',
                WebDriverBy::xpath('//*[@data-record-identifier="pages-36"]'),
            );
        });
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            $driver->click(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-35"]//*[contains(@class, "in2publish-link-publish")]/*[contains(@class, "in2publish-icon-publish")]',
                ),
            );
        });

        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, '5c.1 Parent not published');
            self::assertPageContains($driver, '5c.1.1 Child Ready to Publish');

            // Not publishable exclamation triangle
            self::assertElementIsNotVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-35"]//*[contains(@class, "in2publish-link-publish")]/*[contains(@class, "in2publish-icon-publish")]',
                ),
            );

            // Not publishable exclamation triangle
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[contains(@class, "in2publish-link-publish")]/*[contains(@class, "in2publish-icon-publish")]',
                ),
            );
        });

        $localDriver->close();
        unset($localDriver);
    }
}
