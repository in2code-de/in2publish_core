<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Query;

use In2code\In2publishCore\Component\TcaHandling\TempRecordIndex;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\MmDatabaseRecord;
use In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\Domain\Repository\DualDatabaseRepository;

use function array_keys;
use function array_merge;

class QueryService
{
    protected DualDatabaseRepository $dualDatabaseRepository;

    public function injectDualDatabaseRepository(DualDatabaseRepository $dualDatabaseRepository): void
    {
        $this->dualDatabaseRepository = $dualDatabaseRepository;
    }

    public function resolveDemand(array $demand, TempRecordIndex $index): array
    {
        $records = [];
        if (!empty($demand['select'])) {
            $records[] = $this->resolveSelectDemand($demand['select'], $index);
        }
        if (!empty($demand['join'])) {
            $records[] = $this->resolveJoinDemand($demand['join'], $index);
        }
        return array_merge([], ...$records);
    }

    protected function resolveSelectDemand(mixed $select, TempRecordIndex $index)
    {
        $records = [];
        foreach ($select as $table => $tableSelect) {
            foreach ($tableSelect as $additionalWhere => $properties) {
                foreach ($properties as $property => $valueMaps) {
                    $rows = $this->dualDatabaseRepository->findByProperty(
                        $table,
                        $property,
                        array_keys($valueMaps),
                        $additionalWhere
                    );
                    foreach ($rows as $uid => $row) {
                        $record = $index->getRecord($table, $uid);
                        if (null === $record) {
                            $records[] = $record = new DatabaseRecord($table, $uid, $row['local'], $row['foreign']);
                        }
                        $mapValue = $record->getProp($property);
                        $valueMaps[$mapValue]->addChild($record);
                    }
                }
            }
        }
        return $records;
    }

    protected function resolveJoinDemand(mixed $join)
    {
        $records = [];
        foreach ($join as $joinTable => $JoinSelect) {
            foreach ($JoinSelect as $table => $tableSelect) {
                foreach ($tableSelect as $additionalWhere => $properties) {
                    foreach ($properties as $property => $valueMaps) {
                        $rows = $this->dualDatabaseRepository->findByPropertyWithJoin(
                            $joinTable,
                            $table,
                            $property,
                            array_keys($valueMaps),
                            $additionalWhere
                        );
                        foreach ($rows as $mmId => $row) {
                            $mmRecord = new MmDatabaseRecord(
                                $joinTable,
                                $mmId,
                                $row['local']['mmtbl'] ?? [],
                                $row['foreign']['mmtbl'] ?? []
                            );
                            if (!empty($row['local']['table']) || !empty($row['foreign']['table'])) {
                                $uid = $row['local']['table']['uid'] ?? $row['foreign']['table']['uid'];
                                $tableRecord = new DatabaseRecord(
                                    $table,
                                    $uid,
                                    $row['local']['table'] ?? [],
                                    $row['foreign']['table'] ?? []
                                );
                                $mmRecord->addChild($tableRecord);
                            }
                            $mapValue = $mmRecord->getProp($property);
                            $valueMaps[$mapValue]->addChild($mmRecord);
                        }
                    }
                }
            }
        }
        return $records;
    }
}
