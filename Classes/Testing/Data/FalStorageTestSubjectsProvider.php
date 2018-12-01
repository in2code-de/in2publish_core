<?php
declare(strict_types=1);
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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

class FalStorageTestSubjectsProvider implements SingletonInterface
{
    const PURPOSE_CASE_SENSITIVITY = 'caseSensitivity';
    const PURPOSE_DRIVER = 'driver';
    const PURPOSE_MISSING = 'missing';
    const PURPOSE_UNIQUE_TARGET = 'uniqueTarget';

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

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

    public function __construct()
    {
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
    }

    /**
     * @return array
     */
    public function getStoragesForCaseSensitivityTest(): array
    {
        return $this->getStorages(static::PURPOSE_CASE_SENSITIVITY);
    }

    /**
     * @return array
     */
    public function getStoragesForDriverTest(): array
    {
        return $this->getStorages(static::PURPOSE_DRIVER);
    }

    /**
     * @return array
     */
    public function getStoragesForMissingStoragesTest(): array
    {
        return $this->getStorages(static::PURPOSE_MISSING);
    }

    /**
     * @return array
     */
    public function getStoragesForUniqueTargetTest(): array
    {
        return $this->getStorages(static::PURPOSE_UNIQUE_TARGET);
    }

    /**
     * @param $purpose
     * @return array
     */
    protected function getStorages($purpose): array
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
        try {
            $arguments = $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'filterStorages',
                $arguments
            );
        } catch (InvalidSlotException $e) {
        } catch (InvalidSlotReturnException $e) {
        }
        return [
            'local' => $arguments['localStorages'],
            'foreign' => $arguments['foreignStorages'],
        ];
    }

    /**
     * @param Connection $connection
     * @return array
     */
    protected function fetchStorages(Connection $connection): array
    {
        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $rows = $query->select('*')
                      ->from('sys_file_storage')
                      ->where($query->expr()->eq('deleted', 0))
                      ->execute()
                      ->fetchAll(\PDO::FETCH_ASSOC);
        return array_combine(array_column($rows, 'uid'), $rows);
    }
}
