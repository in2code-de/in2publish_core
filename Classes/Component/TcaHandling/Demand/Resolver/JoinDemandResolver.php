<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Demand\Resolver;

use Doctrine\DBAL\Exception;
use In2code\In2publishCore\Component\TcaHandling\Demand\CallerAwareDemandsCollection;
use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Component\TcaHandling\Demand\Resolver\Exception\InvalidDemandException;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Component\TcaHandling\RecordIndex;
use In2code\In2publishCore\Component\TcaHandling\Repository\DualDatabaseRepository;
use In2code\In2publishCore\Component\TcaHandling\Repository\SingleDatabaseRepository;
use In2code\In2publishCore\Domain\Factory\RecordFactory;

use function array_keys;

class JoinDemandResolver implements DemandResolver
{
    protected DualDatabaseRepository $dualDatabaseRepository;
    protected SingleDatabaseRepository $localRepository;
    protected SingleDatabaseRepository $foreignRepository;
    protected RecordFactory $recordFactory;
    protected RecordIndex $recordIndex;

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

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        $joinRowCollection = $this->resolveJoinDemands($demands);
        $this->findMissingTableRecords($joinRowCollection);
        $this->createAndMapMmRecords($joinRowCollection, $recordCollection);
    }

    protected function resolveJoinDemands(Demands $demands): JoinRowCollection
    {
        $joinRowCollection = new JoinRowCollection();
        foreach ($demands->getJoin() as $joinTable => $JoinSelect) {
            foreach ($JoinSelect as $table => $tableSelect) {
                foreach ($tableSelect as $additionalWhere => $properties) {
                    foreach ($properties as $property => $valueMaps) {
                        try {
                            $rows = $this->dualDatabaseRepository->findByPropertyWithJoin(
                                $joinTable,
                                $table,
                                $property,
                                array_keys($valueMaps),
                                $additionalWhere
                            );
                        } catch (Exception $exception) {
                            if ($demands instanceof CallerAwareDemandsCollection) {
                                $callers = [];
                                $meta = $demands->getMeta();
                                if (isset($meta['join'][$joinTable][$table][$additionalWhere][$property])) {
                                    $callers = $meta['join'][$joinTable][$table][$additionalWhere][$property];
                                }
                                $exception = new InvalidDemandException($callers, $exception);
                            }
                            throw $exception;
                        }
                        $joinRowCollection->addRows($joinTable, $table, $rows, $valueMaps, $property);
                    }
                }
            }
        }
        return $joinRowCollection;
    }

    protected function findMissingTableRecords(JoinRowCollection $joinRowCollection): void
    {
        $missingIdentifiers = $joinRowCollection->getMissingIdentifiers();


        foreach ($missingIdentifiers['local'] ?? [] as $table => $joinTables) {
            $identifiers = [];
            foreach ($joinTables as $joinTable => $missingUids) {
                foreach ($missingUids as $identifier => $mmIds) {
                    $identifiers[$identifier][$joinTable] = $mmIds;
                }
            }
            $rows = $this->localRepository->findByProperty($table, 'uid', array_keys($identifiers));
            foreach ($rows as $uid => $row) {
                foreach ($identifiers[$uid] as $joinTable => $mmIds) {
                    foreach ($mmIds as $mmId) {
                        $joinRowCollection->amendRow($joinTable, $table, $mmId, 'local', $row);
                    }
                }
            }
        }

        foreach ($missingIdentifiers['foreign'] ?? [] as $table => $joinTables) {
            $identifiers = [];
            foreach ($joinTables as $joinTable => $missingUids) {
                foreach ($missingUids as $identifier => $mmIds) {
                    $identifiers[$identifier][$joinTable] = $mmIds;
                }
            }
            $rows = $this->foreignRepository->findByProperty($table, 'uid', array_keys($identifiers));
            foreach ($rows as $uid => $row) {
                foreach ($identifiers[$uid] as $joinTable => $mmIds) {
                    foreach ($mmIds as $mmId) {
                        $joinRowCollection->amendRow($joinTable, $table, $mmId, 'foreign', $row);
                    }
                }
            }
        }
    }

    protected function createAndMapMmRecords(
        JoinRowCollection $joinRowCollection,
        RecordCollection $recordCollection
    ): void {
        $allRows = $joinRowCollection->getRows();
        foreach ($allRows as $joinTable => $tables) {
            foreach ($tables as $table => $mmIds) {
                foreach ($mmIds as $mmId => $recordInfo) {
                    $row = $recordInfo['row'];
                    $valueMaps = $recordInfo['valueMaps'];
                    $property = $recordInfo['property'];

                    $mmRecord = $this->recordIndex->getRecord($table, $mmId);
                    if (null === $mmRecord) {
                        $mmRecord = $this->recordFactory->createMmRecord(
                            $joinTable,
                            $mmId,
                            $row['local']['mmtbl'] ?? [],
                            $row['foreign']['mmtbl'] ?? []
                        );
                        if (null === $mmRecord) {
                            continue;
                        }
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
                                if (null !== $tableRecord) {
                                    $recordCollection->addRecord($tableRecord);
                                }
                            }
                            if (null !== $tableRecord) {
                                $mmRecord->addChild($tableRecord);
                            }
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
