<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Repository;

use Doctrine\DBAL\Connection as DbalConnection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use In2code\In2publishCore\Component\Core\Service\Database\DatabaseSchemaServiceInjection;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryHelper;

use function array_column;
use function array_key_exists;
use function hash;
use function json_encode;
use function substr;

use const JSON_THROW_ON_ERROR;

/**
 * Configured in the Services Configuration for local ond foreign.
 * Always inject `$localRepository` or `$foreignRepository` or by the service names
 * `In2code.In2publishCore.Component.Core.LocalDatabaseRepository`
 * or
 * `In2code.In2publishCore.Component.Core.ForeignDatabaseRepository`
 */
class SingleDatabaseRepository
{
    use DatabaseSchemaServiceInjection;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function findByProperty(
        string $table,
        string $property,
        array $values,
        string $andWhere = null
    ): array {
        $query = $this->connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($table)
              ->where(
                  $query->expr()->in(
                      $property,
                      $query->createNamedParameter($values, DbalConnection::PARAM_STR_ARRAY),
                  ),
              );
        if (!empty($andWhere)) {
            $query->andWhere($andWhere);
        }

        if (!empty($GLOBALS['TCA'][$table]['ctrl']['sortby'])) {
            $query->orderBy($GLOBALS['TCA'][$table]['ctrl']['sortby']);
        } elseif (!empty($GLOBALS['TCA'][$table]['ctrl']['default_sortby'])) {
            $orderByClauses = QueryHelper::parseOrderBy($GLOBALS['TCA'][$table]['ctrl']['default_sortby']);
            foreach ($orderByClauses as $orderByClause) {
                if (!empty($orderByClause[0])) {
                    $query->addOrderBy($orderByClause[0], $orderByClause[1]);
                }
            }
        } else {
            $query->orderBy('uid');
        }

        $result = $query->executeQuery();
        return array_column($result->fetchAllAssociative(), null, 'uid');
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function findByPropertyWithJoin(
        string $mmTable,
        string $table,
        string $property,
        array $values,
        string $andWhere = null
    ): array {
        $mmColumns = $this->databaseSchemaService->getColumnNames($mmTable);
        $tableColumns = $this->databaseSchemaService->getColumnNames($table);

        $query = $this->connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();

        // Prefixes must be exactly 6 chars long (see splitting at the end of the method)!
        foreach ($mmColumns as $mmColumn) {
            $query->addSelect($mmTable . '.' . $mmColumn . ' AS mmtbl_' . $mmColumn);
        }
        foreach ($tableColumns as $tableColumn) {
            $query->addSelect($table . '.' . $tableColumn . ' AS table_' . $tableColumn);
        }

        $query->from($mmTable)
              ->leftJoin(
                  $mmTable,
                  $table,
                  $table,
                  $mmTable . '.' . ($property === 'uid_foreign' ? 'uid_local' : 'uid_foreign') . ' = ' . $table . '.uid',
              )
              ->where($query->expr()->in($property, $values));
        if (!empty($andWhere)) {
            $query->andWhere($andWhere);
        }

        if (!empty($GLOBALS['TCA'][$table]['ctrl']['sortby'])) {
            $query->orderBy($table . '.' . $GLOBALS['TCA'][$table]['ctrl']['sortby']);
        } elseif (!empty($GLOBALS['TCA'][$table]['ctrl']['default_sortby'])) {
            $orderByClauses = QueryHelper::parseOrderBy($GLOBALS['TCA'][$table]['ctrl']['default_sortby']);
            foreach ($orderByClauses as $orderByClause) {
                if (!empty($orderByClause[0])) {
                    $query->addOrderBy($table . '.' . $orderByClause[0], $orderByClause[1]);
                }
            }
        } else {
            $query->orderBy('uid');
        }

        $result = $query->executeQuery();

        $rows = $result->fetchAllAssociative();

        // Split the joined rows into the MM-table part and the joined table part.
        $splitRows = [];
        foreach ($rows as $row) {
            $splitRow = [];
            foreach ($row as $column => $value) {
                // Split the prefix into mmtbl/table (0-5) and the actual column name (6-X).
                $splitRow[substr($column, 0, 5)][substr($column, 6)] = $value;
            }
            if (array_key_exists('uid', $splitRow['table']) && null === $splitRow['table']['uid']) {
                unset($splitRow['table']);
            }
            $mmIdentityProperties = [
                $splitRow['mmtbl']['uid_local'],
                $splitRow['mmtbl']['uid_foreign'],
            ];
            if (isset($splitRow['mmtbl']['tablenames'])) {
                $mmIdentityProperties[] = $splitRow['mmtbl']['tablenames'];
            }
            if (isset($splitRow['mmtbl']['fieldname'])) {
                $mmIdentityProperties[] = $splitRow['mmtbl']['fieldname'];
            }
            $splitRows[hash('sha1', json_encode($mmIdentityProperties, JSON_THROW_ON_ERROR))] = $splitRow;
        }
        /*
         * Example return value:
         *  [
         *      'hash12345' => [
         *          'mmtbl' => [
         *              'uid_local' => 1,
         *              'uid_foreign' => 4,
         *              'sorting' => 1,
         *          ],
         *          'table' => [
         *              'uid' => 4,
         *              'pid' => 1,
         *              'sorting' => 15,
         *              'title' => 'foo',
         *          ],
         *      ],
         *      'hash65482' => [
         *          'mmtbl' => [
         *              'uid_local' => 3,
         *              'uid_foreign' => 6,
         *              'sorting' => 2,
         *          ],
         *          'table' => [
         *              'uid' => 6,
         *              'pid' => 1,
         *              'sorting' => 16,
         *              'title' => 'bar',
         *          ],
         *      ]
         *  ]
         */
        return $splitRows;
    }

    public function findByWhere($table, string $andWhere): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($table);
        if (!empty($andWhere)) {
            $query->andWhere($andWhere);
        }

        if (!empty($GLOBALS['TCA'][$table]['ctrl']['sortby'])) {
            $query->orderBy($GLOBALS['TCA'][$table]['ctrl']['sortby']);
        } elseif (!empty($GLOBALS['TCA'][$table]['ctrl']['default_sortby'])) {
            $orderByClauses = QueryHelper::parseOrderBy($GLOBALS['TCA'][$table]['ctrl']['default_sortby']);
            foreach ($orderByClauses as $orderByClause) {
                if (!empty($orderByClause[0])) {
                    $query->addOrderBy($orderByClause[0], $orderByClause[1]);
                }
            }
        } else {
            $query->orderBy('uid');
        }

        $result = $query->executeQuery();
        return array_column($result->fetchAllAssociative(), null, 'uid');
    }

    public function findMmByProperty(string $table, string $property, array $values): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($table)
              ->where(
                  $query->expr()->in(
                      $property,
                      $query->createNamedParameter($values, DbalConnection::PARAM_STR_ARRAY),
                  ),
              );

        $result = $query->executeQuery();
        $rows = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $mmIdentityProperties = [
                $row['uid_local'],
                $row['uid_foreign'],
                $row['tablenames'] ?? null,
                $row['fieldname'] ?? null,
            ];
            $rows[hash('sha1', json_encode($mmIdentityProperties, JSON_THROW_ON_ERROR))] = $row;
        }
        return $rows;
    }
}
