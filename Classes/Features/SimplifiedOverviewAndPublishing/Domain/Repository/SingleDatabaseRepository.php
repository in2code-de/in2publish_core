<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryHelper;

use function array_column;
use function json_encode;

class SingleDatabaseRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findByProperty(string $table, string $property, array $values): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($table)
              ->where($query->expr()->in($property, $values));

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

        if (!empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])) {
            $query->addOrderBy($GLOBALS['TCA'][$table]['ctrl']['languageField']);
        }
        
        $result = $query->execute();
        return array_column($result->fetchAllAssociative(), null, 'uid');
    }

    public function findMm(string $table, string $property, array $values, string $where): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($table)
              ->where($where)
              ->andWhere($query->expr()->in($property, $values));
        $result = $query->execute();

        $rows = [];
        while ($row = $result->fetchAssociative()) {
            $rows[$this->buildRecordIndexIdentifier($row)] = $row;
        }
        return $rows;
    }

    protected function buildRecordIndexIdentifier(array $row): string
    {
        if (!isset($row['uid'])) {
            $parts = [
                'uid_local' => $row['uid_local'],
                'uid_foreign' => $row['uid_foreign'],
            ];
            if (isset($row['sorting'])) {
                $parts['sorting'] = $row['sorting'];
            }
            if (isset($row['tablenames'])) {
                $parts['tablenames'] = $row['tablenames'];
            }
            if (isset($row['fieldname'])) {
                $parts['fieldname'] = $row['fieldname'];
            }
            return json_encode($parts, JSON_THROW_ON_ERROR);
        }
        return (string)$row['uid'];
    }
}
