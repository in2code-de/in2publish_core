<?php
namespace In2code\In2publishCore\Service\Database;

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

/**
 * Class UidReservationService
 */
class UidReservationService
{
    /**
     * @var DatabaseConnection
     */
    protected $localDatabase = null;

    /**
     * @var DatabaseConnection
     */
    protected $foreignDatabase = null;

    /**
     * @var array
     */
    protected $cache = array();

    /**
     * IdentifierReservationService constructor.
     */
    public function __construct()
    {
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
    }

    /**
     * Increases the auto increment value on local and foreign DB
     * until it's two numbers higher than the highest taken uid.
     *
     * @return int
     */
    public function getReservedUid()
    {
        $nextAutoIncrement = (int)max(
            $this->fetchSysFileAutoIncrementFromDatabase($this->localDatabase),
            $this->fetchSysFileAutoIncrementFromDatabase($this->foreignDatabase)
        );

        do {
            // increase the auto increment to "reserve" the previous integer
            $possibleUid = $nextAutoIncrement++;

            // apply the new auto increment on both databases
            $this->setAutoIncrement($nextAutoIncrement);
        } while (!$this->isUidFree($possibleUid));

        return $possibleUid;
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @return string
     */
    protected function determineDatabaseOfConnection(DatabaseConnection $databaseConnection)
    {
        $cacheKey = spl_object_hash($databaseConnection);
        if (!isset($this->cache[$cacheKey])) {
            $queryResult = $databaseConnection->admin_query('SELECT DATABASE() as db_name;');
            if (false === $queryResult) {
                throw new \RuntimeException('Could not select database name from target database', 1475242213);
            }
            $resultData = $queryResult->fetch_assoc();
            if (!isset($resultData['db_name'])) {
                throw new \RuntimeException('Could not fetch database name from query result', 1475242337);
            }
            $this->cache[$cacheKey] = $resultData['db_name'];
        }
        return $this->cache[$cacheKey];
    }

    /**
     * @param int $autoIncrement
     */
    protected function setAutoIncrement($autoIncrement)
    {
        foreach (array($this->localDatabase, $this->foreignDatabase) as $databaseConnection) {
            $success = $databaseConnection->admin_query(
                'ALTER TABLE sys_file AUTO_INCREMENT = ' . (int)$autoIncrement
            );
            if (false === $success) {
                throw new \RuntimeException('Failed to increase auto_increment on sys_file', 1475248851);
            }
        }
    }

    /**
     * @param int $uid
     * @return bool
     */
    protected function isUidFree($uid)
    {
        return 0 === $this->localDatabase->exec_SELECTcountRows('uid', 'sys_file', 'uid=' . (int)$uid)
               && 0 === $this->foreignDatabase->exec_SELECTcountRows('uid', 'sys_file', 'uid=' . (int)$uid);
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @return int
     */
    protected function fetchSysFileAutoIncrementFromDatabase(DatabaseConnection $databaseConnection)
    {
        $queryResult = $databaseConnection->admin_query(
            'SHOW TABLE STATUS FROM '
            . '`' . $this->determineDatabaseOfConnection($databaseConnection) . '`'
            . ' WHERE name LIKE "sys_file";'
        );
        if (false === $queryResult) {
            throw new \RuntimeException('Could not select table status from database', 1475242494);
        }
        $resultData = $queryResult->fetch_assoc();
        if (!isset($resultData['Auto_increment'])) {
            throw new \RuntimeException('Could not fetch Auto_increment value from query result', 1475242706);
        }
        return (int)$resultData['Auto_increment'];
    }
}
