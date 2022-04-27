<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Query;

use In2code\In2publishCore\Component\TcaHandling\RecordIndex;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordTree;
use In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\Domain\Repository\DualDatabaseRepository;

use function array_keys;
use function array_merge_recursive;
use function In2code\In2publishCore\merge_record;
use function In2code\In2publishCore\merge_records;

class QueryService
{
    protected DualDatabaseRepository $dualDatabaseRepository;
    protected RecordFactory $recordFactory;
    protected RecordIndex $recordIndex;

    public function injectDualDatabaseRepository(DualDatabaseRepository $dualDatabaseRepository): void
    {
        $this->dualDatabaseRepository = $dualDatabaseRepository;
    }

    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
    }

    public function injectRecordIndex(RecordIndex $recordIndex): void
    {
        $this->recordIndex = $recordIndex;
    }

    /**
     * @param array<string, array<string, array<string, array<string, array<Record|RecordTree>>>>> $demand
     * @return array<string, array<int|string, Record>>
     */
    public function resolveDemand(array $demand): array
    {
        $return = [];
        if (!empty($demand['select'])) {
            $records = $this->resolveSelectDemand($demand['select']);
            merge_records($return, $records);
        }
        if (!empty($demand['join'])) {
            $records[] = $this->resolveJoinDemand($demand['join']);
            merge_records($return, $records);
        }
        return $return;
    }

    /**
     * @param array<string, array<string, array<string, array<Record|RecordTree>>>> $select
     * @return array<string, array<int|string, Record>>
     */
    protected function resolveSelectDemand(array $select): array
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
                        $record = $this->recordIndex->getRecord($table, $uid);
                        if (null === $record) {
                            $record = $this->recordFactory->createDatabaseRecord(
                                $table,
                                $uid,
                                $row['local'],
                                $row['foreign']
                            );
                            merge_record($records, $record);
                        }
                        $mapValue = $record->getProp($property);
                        $valueMaps[$mapValue]->addChild($record);
                    }
                }
            }
        }
        return $records;
    }

    /**
     * @param array<string, array<string, array<string, array<string, array<Record|RecordTree>>>>> $join
     * @return array
     */
    protected function resolveJoinDemand(array $join)
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
                            $mmRecord = $this->recordIndex->getRecord($table, $mmId);
                            if (null === $mmRecord) {
                                $mmRecord = $this->recordFactory->createMmRecord(
                                    $joinTable,
                                    $mmId,
                                    $row['local']['mmtbl'] ?? [],
                                    $row['foreign']['mmtbl'] ?? []
                                );
                                if (!empty($row['local']['table']) || !empty($row['foreign']['table'])) {
                                    $uid = $row['local']['table']['uid'] ?? $row['foreign']['table']['uid'];
                                    $tableRecord = $this->recordIndex->getRecord($table, $uid);
                                    if (null === $tableRecord) {
                                        $tableRecord = $this->recordFactory->createDatabaseRecord(
                                            $table,
                                            $uid,
                                            $row['local']['table'] ?? [],
                                            $row['foreign']['table'] ?? []
                                        );
//                                    $records = merge_record($records, $tableRecord);
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
