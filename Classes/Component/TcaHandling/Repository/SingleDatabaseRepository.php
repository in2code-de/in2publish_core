<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Repository;

use In2code\In2publishCore\Component\TcaHandling\Service\Database\DatabaseSchemaService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryHelper;

use function array_column;
use function hash;
use function json_encode;
use function substr;

class SingleDatabaseRepository
{
    private Connection $connection;
    private DatabaseSchemaService $columnNameService;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function injectColumnNameService(DatabaseSchemaService $columnNameService): void
    {
        $this->columnNameService = $columnNameService;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
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
              ->where($query->expr()->in($property, $values));
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

        $result = $query->execute();
        return array_column($result->fetchAllAssociative(), null, 'uid');
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findByPropertyWithJoin(
        string $mmTable,
        string $table,
        string $property,
        array $values,
        string $andWhere = null
    ): array {
        $mmColumns = $this->columnNameService->getColumnNames($mmTable);
        $tableColumns = $this->columnNameService->getColumnNames($table);

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
                  $mmTable . '.' . ($property === 'uid_foreign' ? 'uid_local' : 'uid_foreign') . ' = ' . $table . '.uid'
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

        $result = $query->execute();

        $rows = $result->fetchAllAssociative();

        // Split the joined rows into the MM-table part and the joined table part.
        $splittedRows = [];
        foreach ($rows as $row) {
            $splittedRow = [];
            foreach ($row as $column => $value) {
                // Split the prefix into mmtbl/table (0-5) and the actual column name (6-X).
                $splittedRow[substr($column, 0, 5)][substr($column, 6)] = $value;
            }
            $splittedRows[hash('sha1', json_encode($splittedRow['mmtbl']))] = $splittedRow;
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
        return $splittedRows;
    }
}
