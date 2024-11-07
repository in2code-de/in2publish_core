<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Select;

use Doctrine\DBAL\Exception;
use In2code\In2publishCore\Component\Core\Demand\CallerAwareDemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Exception\InvalidDemandException;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndexInjection;
use In2code\In2publishCore\Component\Core\Repository\DualDatabaseRepositoryInjection;
use In2code\In2publishCore\Component\Core\Repository\ForeignSingleDatabaseRepositoryInjection;
use In2code\In2publishCore\Component\Core\Repository\LocalSingleDatabaseRepositoryInjection;

use function array_key_exists;
use function array_keys;
use function array_unique;

class SelectDemandResolver implements DemandResolver
{
    use RecordFactoryInjection;
    use RecordIndexInjection;
    use DualDatabaseRepositoryInjection;
    use LocalSingleDatabaseRepositoryInjection;
    use ForeignSingleDatabaseRepositoryInjection;

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        $selectRowCollection = $this->resolveSelectDemand($demands);
        $this->findMissingRecordsByUid($selectRowCollection);

        $this->createAndMapRecords($selectRowCollection, $recordCollection);
    }

    protected function resolveSelectDemand(Demands $demands): SelectRowCollection
    {
        $rowCollection = new SelectRowCollection();
        foreach ($demands->getDemandsByType(SelectDemand::class) as $table => $tableSelect) {
            foreach ($tableSelect as $additionalWhere => $properties) {
                foreach ($properties as $property => $valueMaps) {
                    try {
                        $rows = $this->dualDatabaseRepository->findByProperty(
                            $table,
                            $property,
                            array_keys($valueMaps),
                            $additionalWhere,
                        );
                    } catch (Exception $exception) {
                        if ($demands instanceof CallerAwareDemandsCollection) {
                            $callers = $demands->getMeta(SelectDemand::class, $table, $additionalWhere, $property);
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
                        $row['foreign'],
                    );
                    if (null === $record) {
                        continue;
                    }
                } else {
                    $this->recordIndex->addRecord($record);
                }

                $recordCollection->addRecord($record);
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
