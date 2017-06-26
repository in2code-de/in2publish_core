<?php
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
use TYPO3\CMS\Core\Database\DatabaseConnection;
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
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

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
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(get_class($this));
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);
    }

    /**
     * Fetches an array of properties from the given database where the
     * column "$this->identifierFieldName" equals $identifier
     *
     * @param DatabaseConnection $databaseConnection
     * @param int $identifier
     * @return array
     */
    protected function getPropertiesForIdentifier(DatabaseConnection $databaseConnection, $identifier)
    {
        return (array)$databaseConnection->exec_SELECTgetSingleRow(
            '*',
            $this->tableName,
            $this->identifierFieldName . '="' . $this->quoteString($identifier) . '"'
        );
    }

    /**
     * Fetches an array of property arrays (plural !!!) from
     * the given database connection where the column
     * "$propertyName" equals $propertyValue
     *
     * @param DatabaseConnection $databaseConnection
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
        DatabaseConnection $databaseConnection,
        $propertyName,
        $propertyValue,
        $additionalWhere = '',
        $groupBy = '',
        $orderBy = '',
        $limit = '',
        $indexField = 'uid'
    ) {
        if (!empty($this->tableName)) {
            $propertyNameQuoted = $this->quoteString($propertyName);
            $propertyValueQuoted = $this->quoteString($propertyValue);
            $sortingField = $this->tcaService->getSortingField($this->tableName);
            if (empty($orderBy) && !empty($sortingField) && stripos($additionalWhere, 'ORDER BY') === false) {
                $orderBy = $sortingField . ' ASC';
            }
            if (strpos($indexField, ',')) {
                $combinedIdentifier = explode(',', $indexField);
                $rows = (array)$databaseConnection->exec_SELECTgetRows(
                    '*',
                    $this->tableName,
                    $propertyNameQuoted . ' LIKE "' . $propertyValueQuoted . '" ' . $additionalWhere,
                    $groupBy,
                    $orderBy,
                    $limit,
                    ''
                );
                $propertyArray = array();
                foreach ($rows as $row) {
                    $identifierArray = array();
                    foreach ($combinedIdentifier as $identifierFieldName) {
                        $identifierArray[] = $row[$identifierFieldName];
                    }
                    $propertyArray[implode(',', $identifierArray)] = $row;
                }
                return $propertyArray;
            }
            return (array)$databaseConnection->exec_SELECTgetRows(
                '*',
                $this->tableName,
                $propertyNameQuoted . ' LIKE "' . $propertyValueQuoted . '" ' . $additionalWhere,
                $groupBy,
                $orderBy,
                $limit,
                $indexField
            );
        }
        return array();
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @param array $properties
     * @param string $additionalWhere
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     * @param string $indexField
     * @return array
     */
    public function findPropertiesByProperties(
        DatabaseConnection $databaseConnection,
        array $properties,
        $additionalWhere = '',
        $groupBy = '',
        $orderBy = '',
        $limit = '',
        $indexField = 'uid'
    ) {
        $whereParts = array();
        foreach ($properties as $propertyName => $propertyValue) {
            $whereParts[] = $databaseConnection->quoteStr($propertyName, $this->tableName) . ' LIKE '
                            . $databaseConnection->fullQuoteStr($propertyValue, $this->tableName);
        }
        if (empty($orderBy)) {
            $orderBy = $this->tcaService->getSortingField($this->tableName);
        }
        return (array)$databaseConnection->exec_SELECTgetRows(
            '*',
            $this->tableName,
            implode(' AND ', $whereParts) . $additionalWhere,
            $groupBy,
            $orderBy,
            $limit,
            $indexField
        );
    }

    /**
     * TODO: check if $this->identifierFieldName could be used instead
     *
     * Executes an UPDATE query on the given database connection. This method will
     * overwrite any value given in $properties where uid = $identifier
     *
     * @param DatabaseConnection $databaseConnection
     * @param int $identifier
     * @param array $properties
     * @return bool
     */
    protected function updateRecord(DatabaseConnection $databaseConnection, $identifier, array $properties)
    {
        // deal with MM records, they have (in2publish internal) combined identifiers
        if (strpos($identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);

            $whereArray = array();

            foreach ($identifierArray as $property => $value) {
                $whereArray[] = $property . ' LIKE "' . $this->quoteString($value) . '"';
            }

            $whereClause = implode(' AND ', $whereArray);

            $success = (bool)$databaseConnection->exec_UPDATEquery(
                $this->tableName,
                $whereClause,
                $properties
            );
        } else {
            $success = (bool)$databaseConnection->exec_UPDATEquery(
                $this->tableName,
                'uid=' . $identifier,
                $properties
            );
        }
        if (!$success) {
            $this->logFailedQuery(__METHOD__, $databaseConnection);
        }
        return $success;
    }

    /**
     * Executes an INSERT query on the given database connection. Any value in
     * $properties will be inserted into a new row.
     * if there's no UID it will be set by auto_increment
     *
     * @param DatabaseConnection $databaseConnection
     * @param array $properties
     * @return bool
     */
    protected function addRecord(DatabaseConnection $databaseConnection, array $properties)
    {
        $success = (bool)$databaseConnection->exec_INSERTquery($this->tableName, $properties);
        if (!$success) {
            $this->logFailedQuery(__METHOD__, $databaseConnection);
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
     * @param DatabaseConnection $databaseConnection
     * @param int $identifier
     * @return bool
     * @internal param string $deleteFieldName
     */
    protected function deleteRecord(DatabaseConnection $databaseConnection, $identifier)
    {
        if (strpos($identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);

            $whereArray = array();

            foreach ($identifierArray as $property => $value) {
                $whereArray[] = $property . ' LIKE "' . $this->quoteString($value) . '"';
            }

            $whereClause = implode(' AND ', $whereArray);

            $success = (bool)$databaseConnection->exec_DELETEquery($this->tableName, $whereClause);
            if (!$success) {
                $this->logFailedQuery(__METHOD__, $databaseConnection);
            }
            return $success;
        } else {
            $success = (bool)$databaseConnection->exec_DELETEquery($this->tableName, 'uid=' . (int)$identifier);
            if (!$success) {
                $this->logFailedQuery(__METHOD__, $databaseConnection);
            }
            return $success;
        }
    }

    /**
     * Does not support identifier array!
     *
     * @param DatabaseConnection $databaseConnection
     * @param string|int $identifier
     * @return bool|int
     */
    protected function countRecord(DatabaseConnection $databaseConnection, $identifier)
    {
        $result = $databaseConnection->exec_SELECTcountRows(
            '*',
            $this->tableName,
            $this->identifierFieldName . ' LIKE ' . $databaseConnection->fullQuoteStr($identifier, $this->tableName)
        );
        if (false === $result) {
            $this->logFailedQuery(__METHOD__, $databaseConnection);
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
    protected function quoteString($string)
    {
        return DatabaseUtility::quoteString($string, $this->tableName);
    }

    /**
     * Logs a failed database query with all retrievable information
     *
     * @param $method
     * @param DatabaseConnection $databaseConnection
     * @return void
     */
    protected function logFailedQuery($method, DatabaseConnection $databaseConnection)
    {
        $this->logger->critical(
            $method . ': Query failed.',
            array(
                'errno' => $databaseConnection->sql_errno(),
                'error' => $databaseConnection->sql_error(),
                'tableName' => $this->tableName,
            )
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
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @return BaseRepository
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @param string $tableName
     * @return string
     */
    public function replaceTableName($tableName)
    {
        $replacedTableName = $this->tableName;
        $this->tableName = $tableName;
        return $replacedTableName;
    }

    /**
     * @return string
     */
    public function getIdentifierFieldName()
    {
        return $this->identifierFieldName;
    }
}
