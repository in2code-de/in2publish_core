<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Fal;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use Doctrine\DBAL\Driver\Exception as DriverException;
use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\ForeignFileSystemInfoService;
use In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider;
use In2code\In2publishCore\Testing\Tests\Application\ForeignDatabaseConfigTest;
use In2code\In2publishCore\Testing\Tests\Application\ForeignInstanceTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function array_merge;
use function array_unique;
use function get_class;
use function ltrim;
use function preg_match;
use function uniqid;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UniqueStorageTargetTest implements TestCaseInterface
{
    protected ForeignFileSystemInfoService $foreignFileSystemInfoService;
    protected FalStorageTestSubjectsProvider $testSubjectProvider;
    protected ResourceFactory $resourceFactory;

    public function injectForeignFileSystemInfoService(ForeignFileSystemInfoService $foreignFileSystemInfoService): void
    {
        $this->foreignFileSystemInfoService = $foreignFileSystemInfoService;
    }

    public function injectFalStorageTestSubjectProvider(FalStorageTestSubjectsProvider $testSubjectProvider): void
    {
        $this->testSubjectProvider = $testSubjectProvider;
    }

    public function injectResourceFactory(ResourceFactory $resourceFactory): void
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @return TestResult
     * @throws ReflectionException
     * @throws DriverException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function run(): TestResult
    {
        $storages = $this->testSubjectProvider->getStoragesForUniqueTargetTest();
        $keys = array_unique(array_merge(array_keys($storages['local']), array_keys($storages['foreign'])));

        $messages = [];
        $affectedStorages = [];
        $failedUploads = [];

        $skippedStorages = [];
        $foreignOffline = [];

        foreach ($keys as $key) {
            $storageObject = $this->resourceFactory->getStorageObject($key, $storages['local'][$key]);
            if (!$storageObject->isOnline()) {
                $skippedStorages[] = $storageObject->getName();
                continue;
            }
            $driverProperty = new ReflectionProperty(get_class($storageObject), 'driver');
            $driverProperty->setAccessible(true);
            /** @var DriverInterface $localDriver */
            $localDriver = $driverProperty->getValue($storageObject);

            try {
                do {
                    $uniqueFile = uniqid('tx_in2publish_testfile');
                } while (
                    $localDriver->fileExists($uniqueFile)
                    || $this->foreignFileSystemInfoService->fileExists($storages['foreign'][$key]['uid'], $uniqueFile)
                );
            } catch (RuntimeException $e) {
                if (preg_match('/The storage \d+ is offline/', $e->getMessage())) {
                    $foreignOffline[] = $storageObject->getName();
                    continue;
                }
            }

            $sourceFile = GeneralUtility::tempnam($uniqueFile);

            $addedFile = $localDriver->addFile($sourceFile, $localDriver->getRootLevelFolder(), $uniqueFile);
            if ($uniqueFile === ltrim($addedFile, '/')) {
                if ($this->foreignFileSystemInfoService->fileExists($storages['foreign'][$key]['uid'], $uniqueFile)) {
                    $affectedStorages[] = '[' . $key . '] ' . $storages['local'][$key]['name'];
                }
            } else {
                $failedUploads[] = $key;
            }
            if ($localDriver->fileExists($uniqueFile)) {
                $localDriver->deleteFile($uniqueFile);
            }
        }

        if (!empty($failedUploads)) {
            $messages[] = 'fal.test_file_upload_failed';
            $messages[] = 'Affected Storages:';
            $messages = array_merge($messages, $failedUploads);
        }

        if (!empty($affectedStorages)) {
            $messages[] = 'fal.storage_targets_same';
            $messages[] = 'Affected Storages:';
            $messages = array_merge($messages, $affectedStorages);
        }

        if (!empty($foreignOffline)) {
            $messages[] = 'fal.foreign_offline_storages';
            $messages = array_merge($messages, $foreignOffline);
        }

        if (!empty($messages)) {
            if (!empty($skippedStorages)) {
                $messages[] = 'fal.offline_storage_names';
                $messages = array_merge($messages, $skippedStorages);
            }
            return new TestResult(
                'fal.storage_targets_test_error',
                TestResult::ERROR,
                $messages
            );
        }

        if (!empty($skippedStorages)) {
            return new TestResult(
                'fal.storage_targets_test_skipped',
                TestResult::WARNING,
                array_merge(['fal.offline_storage_names'], $skippedStorages)
            );
        }

        return new TestResult('fal.storage_targets_okay');
    }

    public function getDependencies(): array
    {
        return [
            ForeignDatabaseConfigTest::class,
            ForeignInstanceTest::class,
            MissingStoragesTest::class,
            CaseSensitivityTest::class,
            IdenticalDriverTest::class,
        ];
    }
}
