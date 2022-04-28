<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Query;

use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Component\TcaHandling\RecordIndex;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordTree;
use In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\Domain\Repository\DualDatabaseRepository;

use function array_keys;

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
     * @param array<string, array<string, array<string, array<string, array<int, array<Record|RecordTree>>>>>> $demand
     * @return RecordCollection<int, Record>
     */
    public function resolveDemand(array $demand): RecordCollection
    {
        $recordCollection = new RecordCollection();
        if (!empty($demand['select'])) {
            $selectRecordCollection = $this->resolveSelectDemand($demand['select']);
            $recordCollection->addRecordCollection($selectRecordCollection);
        }
        if (!empty($demand['join'])) {
            $joinRecordCollection = $this->resolveJoinDemand($demand['join']);
            $recordCollection->addRecordCollection($joinRecordCollection);
        }
        return $recordCollection;
    }

    /**
     * @param array<string, array<string, array<string, array<int, array<Record|RecordTree>>>>> $select
     * @return RecordCollection<int, Record>
     */
    protected function resolveSelectDemand(array $select): RecordCollection
    {
        $recordCollection = new RecordCollection();
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
                            $recordCollection->addRecord($record);
                        }
                        $mapValue = $record->getProp($property);
                        foreach ($valueMaps[$mapValue] as $parent) {
                            $parent->addChild($record);
                        }
                    }
                }
            }
        }
        return $recordCollection;
    }

    /**
     * @param array<string, array<string, array<string, array<string, array<int, array<Record|RecordTree>>>>>> $join
     * @return RecordCollection<int, Record>
     */
    protected function resolveJoinDemand(array $join): RecordCollection
    {
        $recordCollection = new RecordCollection();
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
                            foreach ($valueMaps[$mapValue] as $parent) {
                                $parent->addChild($mmRecord);
                            }
                        }
                    }
                }
            }
        }
        return $recordCollection;
    }
}
