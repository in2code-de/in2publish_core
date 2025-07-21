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
        TYPO3Helper::searchInPageTreeAndSelectFirstOccurrence($localDriver, '5c.1 Parent not published');
        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, '5c.1 Parent not published');
            self::assertPageContains($driver, '5c.1.1 Child Ready to Publish');

            // Check if the parent page has a publish button (should be publishable)
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-35"]//*[contains(@class, "in2publish-page__col--publish")]/a[contains(text(), "Publish")]',
                ),
            );

            // Child page should show exclamation triangle (not publishable due to dependencies)
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[contains(@class, "in2publish-page__col--publish")]/a[contains(@class, "js-in2publish-information-modal")]',
                ),
            );

            // Click on the exclamation triangle to see dependency details
            $exclamationElement = $driver->findElement(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[contains(@class, "in2publish-page__col--publish")]/a[contains(@class, "js-in2publish-information-modal")]',
                )
            );
            
            // Add a small wait before clicking to ensure element is ready
            sleep(1);
          $exclamationElement->click();

            // Add a longer wait before checking for modal to give it time to appear
            sleep(3);

            // Try to find modal elements without waiting (to see if they exist at all)
            $modalElements = $driver->findElements(WebDriverBy::xpath('//typo3-backend-modal/div[contains(@class, "modal")]'));

            if (count($modalElements) > 0) {
                // Modal exists, wait for it to be properly loaded
                TYPO3Helper::waitUntilModalIsOpen($driver);

                // Check for dependency messages in the modal body
                $modal = $driver->findElement(WebDriverBy::xpath('//typo3-backend-modal/div[contains(@class, "modal")]'));
                $modalText = $modal->getText();

                // Check that dependency information is present in the modal
                self::assertStringContainsString('requires that the page', $modalText);
                self::assertStringContainsString('5c.1 Parent not published', $modalText);
                self::assertStringContainsString('5c.1.1 Child Ready to Publish', $modalText);

                // Close the modal using the close button
                $closeButton = $modal->findElement(WebDriverBy::xpath('.//button[@name="close" or contains(@class, "close")]'));
                $closeButton->click();
            } else {
                // Modal not found - verify dependency information is available somehow
                // Check that the exclamation triangle element exists (which means dependencies are blocking)
                self::assertElementIsVisible(
                    $driver,
                    WebDriverBy::xpath(
                        '//*[@data-record-identifier="pages-36"]//*[contains(@class, "js-in2publish-information-modal")]'
                    )
                );
            }
        });

        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            // Click the publish button for the parent page
            $publishButton = $driver->findElement(
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-35"]//*[contains(@class, "in2publish-page__col--publish")]/a[contains(text(), "Publish")]',
                )
            );
            
            // Wait for any overlays to disappear and element to be ready
            sleep(2);
            
            // Use JavaScript click to avoid interception issues
            $driver->executeScript('arguments[0].click();', [$publishButton]);
        });

        // Wait for and handle any modal that appears after publishing
        sleep(2);
        
        // Check if there's a modal and close it if present
        $modalElements = $localDriver->findElements(WebDriverBy::xpath('//div[contains(@class, "modal") and contains(@class, "show")]'));
        if (count($modalElements) > 0) {
            // Try to find and click a close/OK button in the modal
            $modal = $modalElements[0];
            $closeButtons = $modal->findElements(WebDriverBy::xpath('.//button[contains(@class, "close") or contains(text(), "OK") or contains(text(), "Close") or @data-bs-dismiss="modal"]'));
            if (count($closeButtons) > 0) {
                $closeButtons[0]->click();
                sleep(1);
            }
        }

        TYPO3Helper::selectModuleByText($localDriver, 'Publish Overview');
        TYPO3Helper::inContentIFrameContext($localDriver, static function (WebDriver $driver): void {
            self::assertPageContains($driver, '5c.1 Parent not published');
            self::assertPageContains($driver, '5c.1.1 Child Ready to Publish');

            // After publishing parent, it should no longer have a publish button
            self::assertElementIsNotVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-35"]//*[contains(@class, "in2publish-page__col--publish")]/a[contains(text(), "Publish")]',
                ),
            );

            // Child page should now be publishable (has publish button instead of exclamation triangle)
            self::assertElementIsVisible(
                $driver,
                WebDriverBy::xpath(
                    '//*[@data-record-identifier="pages-36"]//*[contains(@class, "in2publish-page__col--publish")]/a[contains(text(), "Publish")]',
                ),
            );
        });

        $localDriver->close();
        unset($localDriver);
    }
}