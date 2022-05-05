<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Query;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Component\TcaHandling\RecordIndex;
use In2code\In2publishCore\Component\TcaHandling\Repository\DualDatabaseRepository;
use In2code\In2publishCore\Component\TcaHandling\Repository\SingleDatabaseRepository;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Event\DemandsWereCollected;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

use function array_key_exists;
use function array_keys;
use function array_unique;

class QueryService
{
    protected DualDatabaseRepository $dualDatabaseRepository;
    protected SingleDatabaseRepository $localRepository;
    protected SingleDatabaseRepository $foreignRepository;
    protected RecordFactory $recordFactory;
    protected RecordIndex $recordIndex;
    protected EventDispatcher $eventDispatcher;

    public function injectDualDatabaseRepository(DualDatabaseRepository $dualDatabaseRepository): void
    {
        $this->dualDatabaseRepository = $dualDatabaseRepository;
    }

    public function injectLocalSingleDatabaseRepository(SingleDatabaseRepository $localRepository): void
    {
        $this->localRepository = $localRepository;
    }

    public function injectForeignSingleDatabaseRepository(SingleDatabaseRepository $foreignRepository): void
    {
        $this->foreignRepository = $foreignRepository;
    }

    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
    }

    public function injectRecordIndex(RecordIndex $recordIndex): void
    {
        $this->recordIndex = $recordIndex;
    }

    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Demands $demands
     * @return RecordCollection<int, Record>
     */
    public function resolveDemands(Demands $demands): RecordCollection
    {
        $this->eventDispatcher->dispatch(new DemandsWereCollected($demands));

        $recordCollection = new RecordCollection();
        $this->resolveSelectDemand($demands, $recordCollection);
        $this->resolveJoinDemand($demands, $recordCollection);
        return $recordCollection;
    }

    protected function resolveSelectDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        foreach ($demands->getSelect() as $table => $tableSelect) {
            $allRows = [];
            foreach ($tableSelect as $additionalWhere => $properties) {
                foreach ($properties as $property => $valueMaps) {
                    $rows = $this->dualDatabaseRepository->findByProperty(
                        $table,
                        $property,
                        array_keys($valueMaps),
                        $additionalWhere
                    );
                    foreach ($rows as $uid => $row) {
                        $allRows[$uid] = [
                            'row' => $row,
                            'valueMaps' => $valueMaps,
                            'property' => $property,
                        ];
                    }
                }
            }

            $this->findMissingRecordsByUid($allRows, $table);
            $this->createAndMapRecords($allRows, $table, $recordCollection);
        }
    }

    /**
     * $allRows must be a reference to save RAM by preventing copy on write through PHP.
     */
    protected function findMissingRecordsByUid(array &$allRows, string $table): void
    {
        $missingUids = [
            'local' => [],
            'foreign' => [],
        ];

        foreach ($allRows as $recordInfo) {
            $row = $recordInfo['row'];
            if (empty($row['local'])) {
                $missingUids['local'][] = $row['foreign']['uid'];
            }
            if (empty($row['foreign'])) {
                $missingUids['foreign'][] = $row['local']['uid'];
            }
        }

        if (!empty($missingUids['local'])) {
            $localRows = $this->localRepository->findByProperty($table, 'uid', $missingUids['local']);
            foreach ($localRows as $uid => $row) {
                $allRows[$uid]['row']['local'] = $row;
            }
        }
        if (!empty($missingUids['foreign'])) {
            $foreignRows = $this->foreignRepository->findByProperty($table, 'uid', $missingUids['foreign']);
            foreach ($foreignRows as $uid => $row) {
                $allRows[$uid]['row']['foreign'] = $row;
            }
        }
    }

    protected function createAndMapRecords(array $allRows, string $table, RecordCollection $recordCollection): void
    {
        foreach ($allRows as $uid => $recordInfo) {
            $row = $recordInfo['row'];
            $valueMaps = $recordInfo['valueMaps'];
            $property = $recordInfo['property'];

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
            $localMapValue = $record->getLocalProps()[$property] ?? null;
            $foreignMapValue = $record->getForeignProps()[$property] ?? null;
            $mapValues = array_unique([$localMapValue, $foreignMapValue]);

            foreach ($mapValues as $mapValue) {
                if (array_key_exists($mapValue, $valueMaps)) {
                    foreach ($valueMaps[$mapValue] as $parent) {
                        $parent->addChild($record);
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
