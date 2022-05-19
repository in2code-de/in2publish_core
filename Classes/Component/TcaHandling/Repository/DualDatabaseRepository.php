<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Repository;

use function array_keys;
use function array_merge;
use function array_unique;

class DualDatabaseRepository
{
    private SingleDatabaseRepository $localRepository;

    private SingleDatabaseRepository $foreignRepository;

    public function __construct(SingleDatabaseRepository $localRepository, SingleDatabaseRepository $foreignRepository)
    {
        $this->localRepository = $localRepository;
        $this->foreignRepository = $foreignRepository;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findByProperty(
        string $table,
        string $property,
        array $values,
        string $additionalWhere = null
    ): array {
        $localRows = $this->localRepository->findByProperty($table, $property, $values, $additionalWhere);
        $foreignRows = $this->foreignRepository->findByProperty($table, $property, $values, $additionalWhere);

        return $this->mergeRowsByIdentifier($localRows, $foreignRows);
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
        string $additionalWhere = null
    ): array {
        $localRows = $this->localRepository->findByPropertyWithJoin($mmTable, $table, $property, $values, $additionalWhere);
        $foreignRows = $this->foreignRepository->findByPropertyWithJoin($mmTable, $table, $property, $values, $additionalWhere);

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
}
