<?php
namespace In2code\In2publishCore\Testing\Tests\Fal;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\PropertyReflection;

/**
 * Class UniqueStorageTargetTest
 */
class UniqueStorageTargetTest implements TestCaseInterface
{
    public function run()
    {
        $resourceFactory = ResourceFactory::getInstance();
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $storages = $localDatabase->exec_SELECTgetRows(
            '*',
            'sys_file_storage',
            'deleted=0 AND is_online=1',
            '',
            '',
            '',
            'uid'
        );

        $affectedStorages = array();
        $failedUploads = array();

        foreach ($storages as $uid => $storage) {
            $storageObject = $resourceFactory->getStorageObject($storage['uid'], $storage);
            $driverProperty = new PropertyReflection(get_class($storageObject), 'driver');
            $driverProperty->setAccessible(true);
            /** @var DriverInterface $localDriver */
            $localDriver = $driverProperty->getValue($storageObject);
            // DO NOT USE GU::MI because rFALd must not be a singleton
            $foreignDriver = new RemoteFileAbstractionLayerDriver();
            $foreignDriver->setStorageUid($uid);
            $foreignDriver->initialize();

            do {
                $uniqueFile = uniqid('tx_in2publish_tesfile');
            } while ($localDriver->fileExists($uniqueFile) || $foreignDriver->fileExists($uniqueFile));

            $sourceFile = GeneralUtility::tempnam($uniqueFile);

            $addedFile = $localDriver->addFile($sourceFile, $localDriver->getRootLevelFolder(), $uniqueFile);
            if ($uniqueFile === ltrim($addedFile, '/')) {
                $foreignDriver->clearCache();
                if ($foreignDriver->fileExists($uniqueFile)) {
                    $affectedStorages[] = '[' . $uid . '] ' . $storage['name'];
                }
            } else {
                $failedUploads[] = $uid;
            }
            if ($localDriver->fileExists($uniqueFile)) {
                $localDriver->deleteFile($uniqueFile);
            }
        }

        if (!empty($failedUploads)) {
            return new TestResult(
                'fal.test_file_upload_failed',
                TestResult::ERROR,
                array('Affected Storages: ' . implode(',', $failedUploads))
            );
        }

        if (!empty($affectedStorages)) {
            return new TestResult(
                'fal.storage_targets_same',
                TestResult::ERROR,
                array('Affected Storages: ' . implode(',', $affectedStorages), 'fal.xsp_notice')
            );
        }

        return new TestResult('fal.storage_targets_different');
    }

    public function getDependencies()
    {
        return array(
            'In2code\\In2publishCore\\Testing\\Tests\\Fal\\ResourceStorageTest',
        );
    }
}
