<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\SysRedirect;

use Exception;
use In2code\In2publishCore\Component\Core\Demand\CallerAwareDemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\SysRedirectDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Exception\InvalidDemandException;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndexInjection;
use In2code\In2publishCore\Component\Core\Repository\DualDatabaseRepositoryInjection;
use In2code\In2publishCore\Component\Core\Repository\ForeignSingleDatabaseRepositoryInjection;
use In2code\In2publishCore\Component\Core\Repository\LocalSingleDatabaseRepositoryInjection;

class SysRedirectSelectDemandResolver implements DemandResolver
{
    use RecordFactoryInjection;
    use RecordIndexInjection;
    use DualDatabaseRepositoryInjection;
    use LocalSingleDatabaseRepositoryInjection;
    use ForeignSingleDatabaseRepositoryInjection;

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        $selectRowCollection = $this->resolveSelectWithoutPropertyDemand($demands);
        $this->findMissingRecordsByUid($selectRowCollection);

        $this->createAndMapRecords($selectRowCollection, $recordCollection);
    }

    protected function resolveSelectWithoutPropertyDemand(Demands $demands): SysRedirectRowCollection
    {
        $rowCollection = new SysRedirectRowCollection();
        foreach ($demands->getDemandsByType(SysRedirectDemand::class) as $table => $wheres) {
            foreach ($wheres as $where => $parentRecords) {
                try {
                    $rows = $this->dualDatabaseRepository->findByWhere($table, $where);
                } catch (Exception $exception) {
                    if ($demands instanceof CallerAwareDemandsCollection) {
                        $callers = $demands->getMeta(SysRedirectDemand::class, $table, $where);
                        $exception = new InvalidDemandException($callers, $exception);
                    }
                    throw $exception;
                }
                $rowCollection->addRows($table, $rows, $parentRecords);
            }
        }
        return $rowCollection;
    }

    protected function findMissingRecordsByUid(SysRedirectRowCollection $rowCollection): void
    {
        $missingIdentifiers = $rowCollection->getMissingIdentifiers();

        foreach ($missingIdentifiers['local'] ?? [] as $table => $missingIdentifier) {
            $rows = $this->localRepository->findByProperty($table, 'uid', $missingIdentifier);
            $rowCollection->amendRows($table, 'local', $rows);
        }

        foreach ($missingIdentifiers['foreign'] ?? [] as $table => $missingIdentifier) {
            $rows = $this->foreignRepository->findByProperty($table, 'uid', $missingIdentifier);
            $rowCollection->amendRows($table, 'foreign', $rows);
        }
    }

    protected function createAndMapRecords(
        SysRedirectRowCollection $rowCollection,
        RecordCollection $recordCollection
    ): void {
        foreach ($rowCollection->getRows() as $table => $records) {
            foreach ($records as $uid => $recordInfo) {
                $row = $recordInfo['row'];
                $parentRecords = $recordInfo['parentRecords'];

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
                    $recordCollection->addRecord($record);
                }

                foreach ($parentRecords as $parentRecord) {
                    $parentRecord->addChild($record);
                }
            }
        }
    }
}
