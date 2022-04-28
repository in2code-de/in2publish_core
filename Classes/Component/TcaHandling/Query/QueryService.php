<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Query;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Component\TcaHandling\RecordIndex;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
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
     * @param Demands $demands
     * @return RecordCollection<int, Record>
     */
    public function resolveDemand(Demands $demands): RecordCollection
    {
        $recordCollection = new RecordCollection();
        $this->resolveSelectDemand($demands, $recordCollection);
        $this->resolveJoinDemand($demands, $recordCollection);
        return $recordCollection;
    }

    protected function resolveSelectDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        foreach ($demands->getSelect() as $table => $tableSelect) {
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
    }

    protected function resolveJoinDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        foreach ($demands->getJoin() as $joinTable => $JoinSelect) {
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
                                        $recordCollection->addRecord($tableRecord);
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
    }
}
