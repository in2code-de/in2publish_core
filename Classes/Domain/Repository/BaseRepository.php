<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Repository;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
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
use In2code\In2publishCore\Domain\Repository\Exception\MissingArgumentException;
use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use function array_column;
use function explode;
use function implode;
use function preg_match;
use function stripos;
use function strpos;
use function strtoupper;
use function substr;
use function trim;

/**
 * Class BaseRepository. Inherit from this repository to execute methods
 * on a specific database connection. this repository does not
 * own a database connection.
 */
abstract class BaseRepository
{
    public const ADDITIONAL_ORDER_BY_PATTERN = '/(?P<where>.*)ORDER[\s\n]+BY[\s\n]+(?P<col>\w+(\.\w+)?)(?P<dir>\s(DESC|ASC))?/is';
    public const DEPRECATION_METHOD = 'The method %s is deprecated and will be removed in in2publish_core version 10.';
    public const DEPRECATION_PARAMETER = 'The parameter %s of method %s is deprecated and will be removed in in2publish_core version 10.';

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
        string $propertyName,
        $propertyValue,
        string $additionalWhere = '',
        string $groupBy = '',
        string $orderBy = '',
        string $limit = '',
        string $indexField = 'uid',
        string $tableName = null
    ): array {
        $propertyArray = [];

        if (null === $tableName) {
            throw new MissingArgumentException('tableName');
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

        if (is_array($propertyValue)) {
            foreach ($propertyValue as $idx => $value) {
                $propertyValue[$idx] = $query->getConnection()->quote($value);
            }
            $constraint = $query->expr()->in($propertyName, $propertyValue);
        } elseif (is_int($propertyValue) || MathUtility::canBeInterpretedAsInteger($propertyValue)) {
            $constraint = $query->expr()->eq($propertyName, $query->createNamedParameter($propertyValue));
        } else {
            $constraint = $query->expr()->like($propertyName, $query->createNamedParameter($propertyValue));
        }

        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($tableName)
              ->where($constraint);
        if (!empty($additionalWhere)) {
            $query->andWhere($additionalWhere);
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
        $rows = $query->execute()->fetchAllAssociative();

        return $this->indexRowsByField($indexField, $rows);
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
        string $additionalWhere = '',
        string $groupBy = '',
        string $orderBy = '',
        string $limit = '',
        string $indexField = 'uid',
        string $tableName = null
    ): array {
        if (null === $tableName) {
            throw new MissingArgumentException('tableName');
        }

        if (empty($orderBy)) {
            $orderBy = $this->tcaService->getSortingField($tableName);
        }

        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($tableName);
        if (!empty($additionalWhere)) {
            $query->andWhere($additionalWhere);
        }

        foreach ($properties as $propertyName => $propertyValue) {
            if (null === $propertyValue) {
                $query->andWhere($query->expr()->isNull($propertyName));
            } elseif (is_int($propertyValue) || MathUtility::canBeInterpretedAsInteger($propertyValue)) {
                $query->andWhere($query->expr()->eq($propertyName, $query->createNamedParameter($propertyValue)));
            } else {
                $query->andWhere($query->expr()->like($propertyName, $query->createNamedParameter($propertyValue)));
            }
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
        $rows = $query->execute()->fetchAllAssociative();

        return $this->indexRowsByField($indexField, $rows);
    }

    /**
     * Executes an UPDATE query on the given database connection. This method will
     * overwrite any value given in $properties where uid = $identifier
     *
     * @param Connection $connection
     * @param int|string $identifier
     * @param array $properties
     * @param string $tableName
     *
     * @return bool
     */
    protected function updateRecord(
        Connection $connection,
        $identifier,
        array $properties,
        string $tableName
    ): bool {
        // deal with MM records, they have (in2publish internal) combined identifiers
        if (strpos((string)$identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);
        } else {
            $identifierArray = ['uid' => $identifier];
        }
        $connection->update($tableName, $properties, $identifierArray);

        return true;
    }

    /**
     * Executes an INSERT query on the given database connection. Any value in
     * $properties will be inserted into a new row.
     * if there's no UID it will be set by auto_increment
     *
     * @param Connection $connection
     * @param array $properties
     * @param string $tableName
     */
    protected function addRecord(Connection $connection, array $properties, string $tableName): void
    {
        $connection->insert($tableName, $properties);
    }

    /**
     * Removes a database row from the given database connection. Executes a DELETE
     * query where uid = $identifier
     * !!! THIS METHOD WILL REMOVE THE MATCHING ROW FOREVER AND IRRETRIEVABLY !!!
     *
     * If you want to delete a row "the normal way" set
     * propertiesArray('deleted' => TRUE) and use updateRecord()
     *
     * @param Connection $connection
     * @param int|string $identifier
     * @param string $tableName
     *
     * @internal param string $deleteFieldName
     */
    protected function deleteRecord(Connection $connection, $identifier, string $tableName): void
    {
        if (strpos((string)$identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);
        } else {
            $identifierArray = ['uid' => (int)$identifier];
        }
        $connection->delete($tableName, $identifierArray);
    }

    /**
     * Does not support identifier array!
     *
     * @param Connection $connection
     * @param string|int $identifier
     * @param string|null $tableName
     * @param string $idFieldName
     * @return int
     */
    protected function countRecord(
        Connection $connection,
        $identifier,
        string $tableName,
        string $idFieldName = 'uid'
    ): int {
        return $connection->count('*', $tableName, [$idFieldName => $identifier]);
    }

    /**
     * Sets a new index for all entries in $rows. Does not check for duplicate keys.
     * If there are duplicates, the last one is final.
     *
     * @param string $indexField Single field name or comma separated, if more than one field.
     * @param array $rows The rows to reindex
     * @return array The rows with the new index.
     */
    protected function indexRowsByField(string $indexField, array $rows): array
    {
        if (strpos($indexField, ',')) {
            $newRows = [];
            $combinedIdentifier = explode(',', $indexField);

            foreach ($rows as $row) {
                $identifierArray = [];
                foreach ($combinedIdentifier as $identifierFieldName) {
                    $identifierArray[] = $row[$identifierFieldName];
                }
                $newRows[implode(',', $identifierArray)] = $row;
            }
            return $newRows;
        }

        return array_column($rows, null, $indexField);
    }
}
