<?php
namespace In2code\In2publishCore\Testing\Data;

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

use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Class FalStorageTestSubjectsProvider
 */
class FalStorageTestSubjectsProvider implements SingletonInterface
{
    const PURPOSE_CASE_SENSITIVITY = 'caseSensitivity';
    const PURPOSE_DRIVER = 'driver';
    const PURPOSE_MISSING = 'missing';
    const PURPOSE_UNIQUE_TARGET = 'uniqueTarget';

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher = null;

    /**
     * @var array
     */
    protected $localStorages = [];

    /**
     * @var array
     */
    protected $foreignStorages = [];

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * FalStorageTestSubjectsProvider constructor.
     */
    public function __construct()
    {
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
    }

    /**
     * @return array
     */
    public function getStoragesForCaseSensitivityTest()
    {
        return $this->getStorages(self::PURPOSE_CASE_SENSITIVITY);
    }

    /**
     * @return array
     */
    public function getStoragesForDriverTest()
    {
        return $this->getStorages(self::PURPOSE_DRIVER);
    }

    /**
     * @return array
     */
    public function getStoragesForMissingStoragesTest()
    {
        return $this->getStorages(self::PURPOSE_MISSING);
    }

    /**
     * @return array
     */
    public function getStoragesForUniqueTargetTest()
    {
        return $this->getStorages(self::PURPOSE_UNIQUE_TARGET);
    }

    /**
     * @param string $purpose
     * @return array
     */
    protected function getStorages($purpose)
    {
        if (false === $this->initialized) {
            $this->initialized = true;
            $this->localStorages = $this->fetchStorages(DatabaseUtility::buildLocalDatabaseConnection());
            $this->foreignStorages = $this->fetchStorages(DatabaseUtility::buildForeignDatabaseConnection());
        }
        $arguments = [
            'localStorages' => $this->localStorages,
            'foreignStorages' => $this->foreignStorages,
            'purpose' => $purpose,
        ];
        $return = $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            'filterStorages',
            $arguments
        );
        if ($return === $arguments) {
            $localStorages = $arguments['localStorages'];
            $foreignStorages = $arguments['foreignStorages'];
        } else {
            list($localStorages, $foreignStorages) = $return;
        }
        return [
            'local' => $localStorages,
            'foreign' => $foreignStorages,
        ];
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @return array
     */
    protected function fetchStorages(DatabaseConnection $databaseConnection)
    {
        return (array)$databaseConnection->exec_SELECTgetRows('*', 'sys_file_storage', 'deleted=0', '', '', '', 'uid');
    }
}
