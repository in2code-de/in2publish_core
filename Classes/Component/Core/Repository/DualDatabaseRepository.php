<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;

use function array_keys;
use function array_merge;
use function array_unique;

class DualDatabaseRepository
{
    use LocalSingleDatabaseRepositoryInjection;
    use ForeignSingleDatabaseRepositoryInjection;

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
        $localRows = $this->localRepository->findByProperty($table, $property, $values, $andWhere);
        $foreignRows = $this->foreignRepository->findByProperty($table, $property, $values, $andWhere);

        return $this->mergeRowsByIdentifier($localRows, $foreignRows);
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function findByPropertyWithJoin(
        string $mmTable,
        string $table,
        string $property,
        array $values,
        string $andWhere = null
    ): array {
        $localRows = $this->localRepository->findByPropertyWithJoin($mmTable, $table, $property, $values, $andWhere);
        $foreignRows = $this->foreignRepository->findByPropertyWithJoin(
            $mmTable,
            $table,
            $property,
            $values,
            $andWhere,
        );

        return $this->mergeRowsByIdentifier($localRows, $foreignRows);
    }

    protected function mergeRowsByIdentifier(array $localRows, array $foreignRows): array
    {
        $commonIdentifier = array_unique(array_merge(array_keys($localRows), array_keys($foreignRows)));
        $rows = [];
        foreach ($commonIdentifier as $identifier) {
            $rows[$identifier] = [
                'local' => $localRows[$identifier] ?? [],
                'foreign' => $foreignRows[$identifier] ?? [],
                'additional' => [],
            ];
        }
        return $rows;
    }

    public function findByWhere(string $table, string $andWhere): array
    {
        $localRows = $this->localRepository->findByWhere($table, $andWhere);
        $foreignRows = $this->foreignRepository->findByWhere($table, $andWhere);

        return $this->mergeRowsByIdentifier($localRows, $foreignRows);
    }
}
