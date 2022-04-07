<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Query;

use In2code\In2publishCore\Component\TcaHandling\TempRecordIndex;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\MmDatabaseRecord;
use In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\Domain\Repository\DualDatabaseRepository;

use function array_keys;
use function array_merge;

class QueryService
{
    protected DualDatabaseRepository $dualDatabaseRepository;
    protected RecordFactory $recordFactory;

    public function injectDualDatabaseRepository(DualDatabaseRepository $dualDatabaseRepository): void
    {
        $this->dualDatabaseRepository = $dualDatabaseRepository;
    }

    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
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
                            $records[] = $record = $this->recordFactory->createDatabaseRecord($table, $uid, $row['local'], $row['foreign']);
                        }
                        $mapValue = $record->getProp($property);
                        $valueMaps[$mapValue]->addChild($record);
                    }
                }
            }
        }
        return $records;
    }

    protected function resolveJoinDemand(mixed $join, TempRecordIndex $index)
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
                            $mmRecord = $index->getRecord($table, $mmId);
                            if (null === $mmRecord) {
                                $mmRecord = $this->recordFactory->createMmRecord(
                                    $joinTable,
                                    $mmId,
                                    $row['local']['mmtbl'] ?? [],
                                    $row['foreign']['mmtbl'] ?? []
                                );
                                if (!empty($row['local']['table']) || !empty($row['foreign']['table'])) {
                                    $uid = $row['local']['table']['uid'] ?? $row['foreign']['table']['uid'];
                                    $tableRecord = $index->getRecord($table, $uid);
                                    if (null === $tableRecord) {
                                        $tableRecord = $this->recordFactory->createDatabaseRecord(
                                            $table,
                                            $uid,
                                            $row['local']['table'] ?? [],
                                            $row['foreign']['table'] ?? []
                                        );
                                    }
                                    $mmRecord->addChild($tableRecord);
                                }
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
