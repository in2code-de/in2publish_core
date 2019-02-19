<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Features\SimpleOverviewAndAjax\Domain\Repository;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de
 * Alex Kellner <alexander.kellner@in2code.de>,
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

use In2code\In2publishCore\Utility\DatabaseUtility;
use PDO;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;
use function array_column;
use function array_combine;

/**
 * Class TableCacheRepository can save table values to runtime. So another db query may not needed
 */
class TableCacheRepository implements SingletonInterface
{
    /**
     * Hold table values from local database
     *  [
     *      tableName => [
     *          uid => [
     *              property1 => value1
     *          ]
     *      ]
     *  ]
     *
     * @var array
     */
    protected $localCache = [];

    /**
     * Hold table values from foreign database
     *  [
     *      tableName => [
     *          uid => [
     *              property1 => value1
     *          ]
     *      ]
     *  ]
     *
     * @var array
     */
    protected $foreignCache = [];

    /**
     * Get properties from cache by given tableName and uid
     *
     * @param string $tableName
     * @param int $uniqueIdentifier
     * @param string $databaseName
     * @return array
     */
    public function findByUid($tableName, $uniqueIdentifier, $databaseName = 'local'): array
    {
        $cache = $this->getCache($databaseName);
        if (!empty($cache[$tableName][$uniqueIdentifier])) {
            return $cache[$tableName][$uniqueIdentifier];
        }
        $connection = DatabaseUtility::buildDatabaseConnectionForSide($databaseName);
        if ($connection instanceof Connection) {
            $query = $connection->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $row = $query->select('*')
                         ->from($tableName)
                         ->where($query->expr()->eq('uid', (int)$uniqueIdentifier))
                         ->execute()
                         ->fetchAll(PDO::FETCH_ASSOC);
            if (isset($row[0]) && $row[0] === false) {
                return [];
            }
            $this->cacheSingleRecord($tableName, $uniqueIdentifier, $row, $databaseName);
        } else {
            $row = [];
        }
        return $row;
    }

    /**
     * Get properties from cache by given tableName and pid
     *
     * @param string $tableName
     * @param int $pageIdentifier
     * @param string $databaseName
     * @return array
     */
    public function findByPid($tableName, $pageIdentifier, $databaseName = 'local'): array
    {
        $connection = DatabaseUtility::buildDatabaseConnectionForSide($databaseName);
        if ($connection instanceof Connection) {
            $query = DatabaseUtility::buildLocalDatabaseConnection()->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $rows = $query->select('*')
                          ->from($tableName)
                          ->where($query->expr()->eq('pid', (int)$pageIdentifier))
                          ->orderBy('uid', 'ASC')
                          ->execute()
                          ->fetchAll(PDO::FETCH_ASSOC);
            $rows = array_combine(array_column($rows, 'uid'), $rows);
            $this->cacheRecords($tableName, $rows, $databaseName);
        } else {
            $rows = [];
        }
        return $rows;
    }

    /**
     * Store tables in cache
     *
     * @param string $tableName
     * @param array $rows
     * @param string $databaseName
     * @return void
     */
    protected function cacheRecords($tableName, array $rows, $databaseName = 'local')
    {
        foreach ($rows as $row) {
            $this->cacheSingleRecord($tableName, $row['uid'], $row, $databaseName);
        }
    }

    /**
     * Store table properties in cache
     *
     * @param string $tableName
     * @param int $uid
     * @param array $properties
     * @param string $databaseName
     * @return void
     */
    protected function cacheSingleRecord($tableName, $uid, array $properties, $databaseName = 'local')
    {
        $cache = &$this->localCache;
        if ($databaseName === 'foreign') {
            $cache = &$this->foreignCache;
        }
        $cache[$tableName][$uid] = $properties;
    }

    /**
     * @param string $databaseName
     * @return array
     */
    protected function getCache($databaseName = 'local'): array
    {
        $cache = $this->localCache;
        if ($databaseName === 'foreign') {
            $cache = $this->foreignCache;
        }
        return $cache;
    }
}
