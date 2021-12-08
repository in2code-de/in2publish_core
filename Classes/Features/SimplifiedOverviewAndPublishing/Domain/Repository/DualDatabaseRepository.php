<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\Domain\Repository;

use function array_keys;
use function array_merge;
use function array_unique;

class DualDatabaseRepository
{
    /** @var SingleDatabaseRepository */
    private $localRepository;

    /** @var SingleDatabaseRepository */
    private $foreignRepository;

    public function __construct(SingleDatabaseRepository $localRepository, SingleDatabaseRepository $foreignRepository)
    {
        $this->localRepository = $localRepository;
        $this->foreignRepository = $foreignRepository;
    }

    public function findByProperty(string $table, string $property, array $values): array
    {
        $localRows = $this->localRepository->findByProperty($table, $property, $values);
        $foreignRows = $this->foreignRepository->findByProperty($table, $property, $values);

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

    public function findMissingRows(string $table, array $rows): array
    {
        $missingIdentifiers = $this->identifyMissingIdentifiersOnEachSide($rows);
        return $this->amendMissingRows($table, $rows, $missingIdentifiers);
    }

    protected function identifyMissingIdentifiersOnEachSide(array $rows): array
    {
        $missingRecords = [];
        foreach ($rows as $identifier => $rowSet) {
            if ([] === $rowSet['local']) {
                $missingRecords['local'][$identifier] = $identifier;
            } elseif ([] === $rowSet['foreign']) {
                $missingRecords['foreign'][$identifier] = $identifier;
            }
        }
        return $missingRecords;
    }

    protected function amendMissingRows(string $table, array $rows, array $missingRows): array
    {
        if (!empty($missingRows['local'])) {
            $foundRows = $this->localRepository->findByProperty($table, 'uid', $missingRows['local']);
            foreach ($foundRows as $foundRow) {
                $rows[$foundRow['uid']]['local'] = $foundRow;
            }
        }
        if (!empty($missingRows['foreign'])) {
            $foundRows = $this->foreignRepository->findByProperty($table, 'uid', $missingRows['foreign']);
            foreach ($foundRows as $foundRow) {
                $rows[$foundRow['uid']]['foreign'] = $foundRow;
            }
        }
        return $rows;
    }

    public function findMm(string $table, string $property, array $values, string $where): array
    {
        $localRows = $this->localRepository->findMm($table, $property, $values, $where);
        $foreignRows = $this->foreignRepository->findMm($table, $property, $values, $where);

        return $this->mergeRowsByIdentifier($localRows, $foreignRows);
    }
}
