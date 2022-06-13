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

use function array_key_exists;
use function array_keys;
use function array_unique;

class SelectDemandResolver implements DemandResolver
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
        $selectRowCollection = $this->resolveSelectDemand($demands);
        $this->findMissingRecordsByUid($selectRowCollection);

        $this->createAndMapRecords($selectRowCollection, $recordCollection);
    }

    protected function resolveSelectDemand(Demands $demands): SelectRowCollection
    {
        $rowCollection = new SelectRowCollection();
        foreach ($demands->getSelect() as $table => $tableSelect) {
            foreach ($tableSelect as $additionalWhere => $properties) {
                foreach ($properties as $property => $valueMaps) {
                    try {
                        $rows = $this->dualDatabaseRepository->findByProperty(
                            $table,
                            $property,
                            array_keys($valueMaps),
                            $additionalWhere
                        );
                    } catch (Exception $exception) {
                        if ($demands instanceof CallerAwareDemandsCollection) {
                            $callers = [];
                            $meta = $demands->getMeta();
                            if (isset($meta['select'][$table][$additionalWhere][$property])) {
                                $callers = $meta['select'][$table][$additionalWhere][$property];
                            }
                            $exception = new InvalidDemandException($callers, $exception);
                        }
                        throw $exception;
                    }
                    $rowCollection->addRows($table, $rows, $valueMaps, $property);
                }
            }
        }
        return $rowCollection;
    }

    protected function findMissingRecordsByUid(SelectRowCollection $selectRowCollection): void
    {
        $missingIdentifiers = $selectRowCollection->getMissingIdentifiers();

        foreach ($missingIdentifiers['local'] ?? [] as $table => $missingIdentifier) {
            $rows = $this->localRepository->findByProperty($table, 'uid', $missingIdentifier);
            $selectRowCollection->amendRows($table, 'local', $rows);
        }

        foreach ($missingIdentifiers['foreign'] ?? [] as $table => $missingIdentifier) {
            $rows = $this->foreignRepository->findByProperty($table, 'uid', $missingIdentifier);
            $selectRowCollection->amendRows($table, 'foreign', $rows);
        }
    }

    protected function createAndMapRecords(
        SelectRowCollection $selectRowCollection,
        RecordCollection $recordCollection
    ): void {
        foreach ($selectRowCollection->getRows() as $table => $records) {
            foreach ($records as $uid => $recordInfo) {
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
                    if (null === $record) {
                        continue;
                    }
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
    }
}
