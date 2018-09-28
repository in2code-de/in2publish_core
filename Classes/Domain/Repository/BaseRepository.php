<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BaseRepository. Inherit from this repository to execute methods
 * on a specific database connection. this repository does not
 * own a database connection.
 */
abstract class BaseRepository
{
    const ADDITIONAL_ORDER_BY_PATTER = '/(?P<where>.*)ORDER BY (?P<col>\w+(\.\w+)?)(?P<dir>\s(DESC|ASC))?/i';

    /**
     * The table name to use for any SELECT, INSERT, UPDATE and DELETE query
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * @var string
     */
    protected $identifierFieldName = 'uid';

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var TcaService
     */
    protected $tcaService = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);
    }

    /**
     * Fetches an array of property arrays (plural !!!) from
     * the given database connection where the column
     * "$propertyName" equals $propertyValue
     *
     * @param Connection $connection
     * @param string $propertyName
     * @param mixed $propertyValue
     * @param string $additionalWhere
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     * @param string $indexField
     * @return array
     */
    protected function findPropertiesByProperty(
        Connection $connection,
        $propertyName,
        $propertyValue,
        $additionalWhere = '',
        $groupBy = '',
        $orderBy = '',
        $limit = '',
        $indexField = 'uid'
    ): array {
        $propertyArray = [];

        if (empty($this->tableName)) {
            return $propertyArray;
        }
        $sortingField = $this->tcaService->getSortingField($this->tableName);
        if (1 === preg_match(self::ADDITIONAL_ORDER_BY_PATTER, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
            $orderBy = $matches['col'] . strtoupper($matches['dir'] ?? ' ASC');
        }
        if (empty($orderBy) && !empty($sortingField)) {
            $orderBy = $sortingField . ' ASC';
        }
        $additionalWhere = trim($additionalWhere);
        if ('AND' === substr($additionalWhere, 0, 3)) {
            $additionalWhere = trim(substr($additionalWhere, 3));
        }

        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($this->tableName)
              ->where($query->expr()->like($propertyName, $query->createNamedParameter($propertyValue)))
              ->andWhere($additionalWhere);

        if (!empty($groupBy)) {
            $query->groupBy($groupBy);
        }
        if (!empty($orderBy)) {
            $order = explode(' ', $orderBy);
            $query->orderBy($order[0], $order[1] ?? null);
        }
        if (!empty($limit)) {
            $query->setMaxResults((int)$limit);
        }
        $rows = $query->execute()->fetchAll();

        if (strpos($indexField, ',')) {
            $combinedIdentifier = explode(',', $indexField);
            foreach ($rows as $row) {
                $identifierArray = [];
                foreach ($combinedIdentifier as $identifierFieldName) {
                    $identifierArray[] = $row[$identifierFieldName];
                }
                $propertyArray[implode(',', $identifierArray)] = $row;
            }
            return $propertyArray;
        } else {
            foreach ($rows as $row) {
                $propertyArray[$row[$indexField]] = $row;
            }
        }

        return $propertyArray;
    }

    /**
     * @param Connection $connection
     * @param array $properties
     * @param string $additionalWhere
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     * @param string $indexField
     * @return array
     */
    public function findPropertiesByProperties(
        Connection $connection,
        array $properties,
        $additionalWhere = '',
        $groupBy = '',
        $orderBy = '',
        $limit = '',
        $indexField = 'uid'
    ): array {
        if (empty($orderBy)) {
            $orderBy = $this->tcaService->getSortingField($this->tableName);
        }

        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($this->tableName)
              ->andWhere($additionalWhere);

        foreach ($properties as $propertyName => $propertyValue) {
            $query->andWhere($query->expr()->like($propertyName, $query->createNamedParameter($propertyValue)));
        }

        if (!empty($groupBy)) {
            $query->groupBy($groupBy);
        }
        if (!empty($orderBy)) {
            $order = explode(' ', $orderBy);
            $query->orderBy($order[0], $order[1] ?? null);
        }
        if (!empty($limit)) {
            $query->setMaxResults((int)$limit);
        }
        $rows = $query->execute()->fetchAll();
        return array_combine(array_column($rows, $indexField), $rows);
    }

    /**
     * TODO: check if $this->identifierFieldName could be used instead
     *
     * Executes an UPDATE query on the given database connection. This method will
     * overwrite any value given in $properties where uid = $identifier
     *
     * @param Connection $connection
     * @param int $identifier
     * @param array $properties
     * @return bool
     */
    protected function updateRecord(Connection $connection, $identifier, array $properties): bool
    {
        // deal with MM records, they have (in2publish internal) combined identifiers
        if (strpos($identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);

            $success = (bool)$connection->update(
                $this->tableName,
                $properties,
                $identifierArray
            );
        } else {
            $success = (bool)$connection->update(
                $this->tableName,
                $properties,
                ['uid' => $identifier]
            );
        }
        if (!$success) {
            $this->logFailedQuery(__METHOD__, $connection);
        }
        return $success;
    }

    /**
     * Executes an INSERT query on the given database connection. Any value in
     * $properties will be inserted into a new row.
     * if there's no UID it will be set by auto_increment
     *
     * @param Connection $connection
     * @param array $properties
     * @return bool
     */
    protected function addRecord(Connection $connection, array $properties): bool
    {
        $success = (bool)$connection->insert($this->tableName, $properties);
        if (!$success) {
            $this->logFailedQuery(__METHOD__, $connection);
        }
        return $success;
    }

    /**
     * TODO: check if $this->identifierFieldName could be used instead
     *
     * Removes a database row from the given database connection. Executes a DELETE
     * query where uid = $identifier
     * !!! THIS METHOD WILL REMOVE THE MATCHING ROW FOREVER AND IRRETRIEVABLY !!!
     *
     * If you want to delete a row "the normal way" set
     * propertiesArray('deleted' => TRUE) and use updateRecord()
     *
     * @param Connection $connection
     * @param int $identifier
     * @return bool
     * @internal param string $deleteFieldName
     */
    protected function deleteRecord(Connection $connection, $identifier)
    {
        if (strpos($identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);

            $success = (bool)$connection->delete($this->tableName, $identifierArray);
            if (!$success) {
                $this->logFailedQuery(__METHOD__, $connection);
            }
            return $success;
        } else {
            $success = (bool)$connection->delete($this->tableName, ['uid' => (int)$identifier]);
            if (!$success) {
                $this->logFailedQuery(__METHOD__, $connection);
            }
            return $success;
        }
    }

    /**
     * Does not support identifier array!
     *
     * @param Connection $connection
     * @param string|int $identifier
     * @return bool|int
     */
    protected function countRecord(Connection $connection, $identifier)
    {
        $result = $connection->count(
            '*',
            $this->tableName,
            [$this->identifierFieldName => $identifier]
        );
        if (false === $result) {
            $this->logFailedQuery(__METHOD__, $connection);
            return false;
        }
        return (int)$result;
    }

    /**
     * Quote string: escapes bad characters
     *
     * @param string $string
     * @return string
     */
    protected function quoteString($string): string
    {
        return DatabaseUtility::quoteString($string);
    }

    /**
     * Logs a failed database query with all retrievable information
     *
     * @param $method
     * @param Connection $connection
     * @return void
     */
    protected function logFailedQuery($method, Connection $connection)
    {
        $this->logger->critical(
            $method . ': Query failed.',
            [
                'errno' => $connection->errorCode(),
                'error' => $connection->errorInfo(),
                'tableName' => $this->tableName,
            ]
        );
    }

    /*************************
     *                       *
     *  GETTERS AND SETTERS  *
     *                       *
     *************************/

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @return BaseRepository
     */
    public function setTableName($tableName): BaseRepository
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @param string $tableName
     * @return string
     */
    public function replaceTableName($tableName): string
    {
        $replacedTableName = $this->tableName;
        $this->tableName = $tableName;
        return $replacedTableName;
    }

    /**
     * @return string
     */
    public function getIdentifierFieldName(): string
    {
        return $this->identifierFieldName;
    }
}
