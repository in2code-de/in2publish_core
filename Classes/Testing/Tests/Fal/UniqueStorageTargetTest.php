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

use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider;
use In2code\In2publishCore\Testing\Tests\Application\ForeignDatabaseConfigTest;
use In2code\In2publishCore\Testing\Tests\Application\ForeignInstanceTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function array_merge;
use function array_unique;
use function ltrim;
use function uniqid;

class UniqueStorageTargetTest implements TestCaseInterface
{
    /**
     * @var FalStorageTestSubjectsProvider
     */
    protected $testSubjectProvider;

    /**
     * UniqueStorageTargetTest constructor.
     */
    public function __construct()
    {
        $this->testSubjectProvider = GeneralUtility::makeInstance(FalStorageTestSubjectsProvider::class);
    }

    /**
     * @return TestResult
     * @throws ReflectionException
     */
    public function run(): TestResult
    {
        $storages = $this->testSubjectProvider->getStoragesForUniqueTargetTest();
        $keys = array_unique(array_merge(array_keys($storages['local']), array_keys($storages['foreign'])));

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $messages = [];
        $affectedStorages = [];
        $failedUploads = [];

        $skippedStorages = [];
        $foreignOffline = [];

        foreach ($keys as $key) {
            $storageObject = $resourceFactory->getStorageObject($key, $storages['local'][$key]);
            if (!$storageObject->isOnline()) {
                $skippedStorages[] = $storageObject->getName();
                continue;
            }
            $driverProperty = new ReflectionProperty(get_class($storageObject), 'driver');
            $driverProperty->setAccessible(true);
            /** @var DriverInterface $localDriver */
            $localDriver = $driverProperty->getValue($storageObject);
            // DO NOT USE GU::MI because rFALd must not be a singleton
            $foreignDriver = new RemoteFileAbstractionLayerDriver();
            $foreignDriver->setStorageUid($storages['foreign'][$key]['uid']);
            $foreignDriver->initialize();
            if (!$foreignDriver->isOnline()) {
                $foreignOffline[] = $storageObject->getName();
                continue;
            }

            do {
                $uniqueFile = uniqid('tx_in2publish_testfile', false);
            } while ($localDriver->fileExists($uniqueFile) || $foreignDriver->fileExists($uniqueFile));

            $sourceFile = GeneralUtility::tempnam($uniqueFile);

            $addedFile = $localDriver->addFile($sourceFile, $localDriver->getRootLevelFolder(), $uniqueFile);
            if ($uniqueFile === ltrim($addedFile, '/')) {
                $foreignDriver->clearCache();
                if ($foreignDriver->fileExists($uniqueFile)) {
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

    /**
     * @return array
     */
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
