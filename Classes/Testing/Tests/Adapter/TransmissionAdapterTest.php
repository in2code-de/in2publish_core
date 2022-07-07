<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Adapter;

/*
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandResponse;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\AssetTransmitter;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface;
use In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationFormatTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_merge;
use function bin2hex;
use function file_put_contents;
use function is_file;
use function random_bytes;
use function register_shutdown_function;
use function strpos;
use function uniqid;
use function unlink;

class TransmissionAdapterTest implements TestCaseInterface
{
    protected AssetTransmitter $assetTransmitter;
    protected RemoteCommandDispatcher $remoteCommandDispatcher;

    public function __construct(AssetTransmitter $assetTransmitter, RemoteCommandDispatcher $remoteCommandDispatcher)
    {
        $this->assetTransmitter = $assetTransmitter;
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function run(): TestResult
    {
        try {
            $canary = bin2hex(random_bytes(16));
        } catch (Throwable $e) {
            $canary = uniqid('tx_in2publishcore_test_', true);
        }
        $localTmpFile = GeneralUtility::tempnam('tx_in2publishlocal_test_', '.txt');
        file_put_contents($localTmpFile, $canary);
        register_shutdown_function(static fn() => is_file($localTmpFile) && unlink($localTmpFile));

        try {
            $foreignTmpFile = $this->assetTransmitter->transmitTemporaryFile($localTmpFile);
        } catch (Throwable $exception) {
            return new TestResult(
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.testing.xlf:adapter.transmission.asset_transmitter_error',
                TestResult::ERROR,
                [(string)$exception]
            );
        }

        GeneralUtility::unlink_tempfile($localTmpFile);

        $getContentResponse = $this->getForeignFileContents($foreignTmpFile);
        if (!$getContentResponse->isSuccessful()) {
            $needle = 'cat: ' . $foreignTmpFile . ': No such file or directory';
            if (false !== strpos($getContentResponse->getErrorsString(), $needle)) {
                return new TestResult(
                    'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.testing.xlf:adapter.transmission.file_not_transferred',
                    TestResult::ERROR,
                    [$getContentResponse->getOutputString(), $getContentResponse->getErrorsString()]
                );
            }
            $rmResponse = $this->removeForeignFile($foreignTmpFile);
            if ($rmResponse->isSuccessful()) {
                return new TestResult(
                    'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.testing.xlf:adapter.transmission.file_cat_failed',
                    TestResult::ERROR,
                    [$getContentResponse->getOutputString(), $getContentResponse->getErrorsString()]
                );
            }
            return new TestResult(
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.testing.xlf:adapter.transmission.file_cat_failed_manual_remove',
                TestResult::ERROR,
                [
                    $getContentResponse->getOutputString(),
                    $getContentResponse->getErrorsString(),
                    $rmResponse->getOutputString(),
                    $rmResponse->getErrorsString(),
                ],
                [$foreignTmpFile]
            );
        }

        $rmResponse = $this->removeForeignFile($foreignTmpFile);

        if ($getContentResponse->getOutputString() !== $canary) {
            if ($rmResponse->isSuccessful()) {
                return new TestResult(
                    'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.testing.xlf:adapter.transmission.content_altered',
                    TestResult::ERROR,
                    [$getContentResponse->getOutputString(), $getContentResponse->getErrorsString()]
                );
            }
            return new TestResult(
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.testing.xlf:adapter.transmission.content_altered_manual_remove',
                TestResult::ERROR,
                [
                    'Expected: "' . $canary . '"',
                    'Actual: "' . $getContentResponse->getOutputString() . '"',
                    $getContentResponse->getErrorsString(),
                    $rmResponse->getOutputString(),
                    $rmResponse->getErrorsString(),
                ],
                [$foreignTmpFile]
            );
        }

        if (!$rmResponse->isSuccessful()) {
            return new TestResult(
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.testing.xlf:adapter.transmission.file_deletion_failed',
                TestResult::ERROR,
                [$rmResponse->getOutputString(), $rmResponse->getErrorsString()],
                [$foreignTmpFile]
            );
        }

        return new TestResult(
            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.testing.xlf:adapter.transmission.all_tests_passed'
        );
    }

    protected function removeForeignFile(string $foreignTmpFile): RemoteCommandResponse
    {
        $rceRequest = new RemoteCommandRequest('rm', [], [$foreignTmpFile]);
        $rceRequest->setDispatcher('');
        $rceRequest->usePhp(false);

        return $this->remoteCommandDispatcher->dispatch($rceRequest);
    }

    protected function getForeignFileContents(string $foreignTmpFile): RemoteCommandResponse
    {
        $rceRequest = new RemoteCommandRequest('cat', [], [$foreignTmpFile]);
        $rceRequest->setDispatcher('');
        $rceRequest->usePhp(false);

        return $this->remoteCommandDispatcher->dispatch($rceRequest);
    }

    public function getDependencies(): array
    {
        $dependencies = [
            ConfigurationFormatTest::class,
            AdapterSelectionTest::class,
        ];
        if (isset($GLOBALS['in2publish_core']['virtual_tests'][AdapterInterface::class])) {
            $dependencies = array_merge(
                $dependencies,
                $GLOBALS['in2publish_core']['virtual_tests'][AdapterInterface::class]
            );
        }
        return $dependencies;
    }
}
