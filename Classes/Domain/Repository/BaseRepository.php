<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Repository;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de
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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_column;
use function array_combine;
use function explode;
use function json_encode;
use function preg_match;
use function sprintf;
use function stripos;
use function strpos;
use function strtoupper;
use function substr;
use function trigger_error;
use function trim;
use const E_USER_DEPRECATED;

/**
 * Class BaseRepository. Inherit from this repository to execute methods
 * on a specific database connection. this repository does not
 * own a database connection.
 */
abstract class BaseRepository
{
    const ADDITIONAL_ORDER_BY_PATTERN = '/(?P<where>.*)ORDER[\s\n]+BY[\s\n]+(?P<col>\w+(\.\w+)?)(?P<dir>\s(DESC|ASC))?/is';
    const DEPRECATION_TABLE_NAME_FIELD = 'The field BaseRepository::$tableName is deprecated and will be removed in in2publish_core version 10. Please use the methods tableName argument instead. Method: %s';
    const DEPRECATION_METHOD = 'The method %s is deprecated and will be removed in in2publish_core version 10.';
    const DEPRECATION_PARAMETER = 'The parameter %s of method %s is deprecated and will be removed in in2publish_core version 10.';

    /**
     * The table name to use for any SELECT, INSERT, UPDATE and DELETE query
     *
     * @var string
     *
     * @deprecated This property is deprecated and will be removed in in2publish_core version 10.
     *  Use the available method arguments instead.
     */
    protected $tableName = '';

    /**
     * @var string
     *
     * @deprecated This property is deprecated and will be removed in in2publish_core version 10.
     *  Use the available method arguments instead.
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
     * @param string|null $tableName
     *
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
        $indexField = 'uid',
        string $tableName = null
    ): array {
        $propertyArray = [];

        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }

        if (empty($tableName)) {
            return $propertyArray;
        }
        $sortingField = $this->tcaService->getSortingField($tableName);
        if (1 === preg_match(self::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
            $orderBy = $matches['col'] . strtoupper($matches['dir'] ?? ' ASC');
        }
        if (empty($orderBy) && !empty($sortingField)) {
            $orderBy = $sortingField . ' ASC';
        }
        $additionalWhere = trim($additionalWhere);
        if (0 === stripos($additionalWhere, 'and')) {
            $additionalWhere = trim(substr($additionalWhere, 3));
        }

        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($tableName)
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
     * @param string|null $tableName
     *
     * @return array
     */
    public function findPropertiesByProperties(
        Connection $connection,
        array $properties,
        $additionalWhere = '',
        $groupBy = '',
        $orderBy = '',
        $limit = '',
        $indexField = 'uid',
        string $tableName = null
    ): array {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }

        if (empty($orderBy)) {
            $orderBy = $this->tcaService->getSortingField($tableName);
        }

        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($tableName)
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
     * @param int|string $identifier
     * @param array $properties
     * @param string|null $tableName
     *
     * @return bool
     */
    protected function updateRecord(
        Connection $connection,
        $identifier,
        array $properties,
        string $tableName = null
    ): bool {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        // deal with MM records, they have (in2publish internal) combined identifiers
        if (strpos((string)$identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);

            $connection->update(
                $tableName,
                $properties,
                $identifierArray
            );
        } else {
            $connection->update(
                $tableName,
                $properties,
                ['uid' => $identifier]
            );
        }
        if (0 < $connection->errorCode()) {
            $this->logFailedQuery(__METHOD__, $connection, $tableName);
        }
        return true;
    }

    /**
     * Executes an INSERT query on the given database connection. Any value in
     * $properties will be inserted into a new row.
     * if there's no UID it will be set by auto_increment
     *
     * @param Connection $connection
     * @param array $properties
     * @param string|null $tableName
     *
     * @return bool
     */
    protected function addRecord(Connection $connection, array $properties, string $tableName = null): bool
    {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        $success = (bool)$connection->insert($tableName, $properties);
        if (!$success) {
            $this->logFailedQuery(__METHOD__, $connection, $tableName);
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
     * @param string|null $tableName
     *
     * @return bool
     * @internal param string $deleteFieldName
     */
    protected function deleteRecord(Connection $connection, $identifier, string $tableName = null)
    {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        if (strpos((string)$identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);

            $success = (bool)$connection->delete($tableName, $identifierArray);
            if (!$success) {
                $this->logFailedQuery(__METHOD__, $connection, $tableName);
            }
            return $success;
        } else {
            $success = (bool)$connection->delete($tableName, ['uid' => (int)$identifier]);
            if (!$success) {
                $this->logFailedQuery(__METHOD__, $connection, $tableName);
            }
            return $success;
        }
    }

    /**
     * Does not support identifier array!
     *
     * @param Connection $connection
     * @param string|int $identifier
     * @param string|null $tableName
     *
     * @return bool|int
     */
    protected function countRecord(Connection $connection, $identifier, string $tableName = null, $idFieldName = 'uid')
    {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        $result = $connection->count(
            '*',
            $tableName,
            [$idFieldName => $identifier]
        );
        if (false === $result) {
            $this->logFailedQuery(__METHOD__, $connection, $tableName);
            return false;
        }
        return (int)$result;
    }

    /**
     * Quote string: escapes bad characters
     *
     * @param string $string
     *
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
     * @param string|null $tableName
     *
     * @return void
     */
    protected function logFailedQuery($method, Connection $connection, string $tableName = null)
    {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        $this->logger->critical(
            $method . ': Query failed.',
            [
                'errno' => $connection->errorCode(),
                'error' => json_encode($connection->errorInfo()),
                'tableName' => $tableName,
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
        trigger_error(sprintf(static::DEPRECATION_METHOD, __METHOD__), E_USER_DEPRECATED);
        return $this->tableName;
    }

    /**
     * @param string $tableName
     *
     * @return BaseRepository
     */
    public function setTableName($tableName): BaseRepository
    {
        trigger_error(sprintf(static::DEPRECATION_METHOD, __METHOD__), E_USER_DEPRECATED);
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    public function replaceTableName($tableName): string
    {
        trigger_error(sprintf(static::DEPRECATION_METHOD, __METHOD__), E_USER_DEPRECATED);
        $replacedTableName = $this->tableName;
        $this->tableName = $tableName;
        return $replacedTableName;
    }

    /**
     * @return string
     */
    public function getIdentifierFieldName(): string
    {
        trigger_error(sprintf(static::DEPRECATION_METHOD, __METHOD__), E_USER_DEPRECATED);
        return $this->identifierFieldName;
    }
}
