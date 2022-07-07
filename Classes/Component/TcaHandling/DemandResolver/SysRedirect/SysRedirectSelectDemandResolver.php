<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\DemandResolver\SysRedirect;

use Exception;
use In2code\In2publishCore\Component\TcaHandling\Demand\CallerAwareDemandsCollection;
use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Component\TcaHandling\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\TcaHandling\DemandResolver\Exception\InvalidDemandException;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Component\TcaHandling\RecordIndex;
use In2code\In2publishCore\Component\TcaHandling\Repository\DualDatabaseRepository;
use In2code\In2publishCore\Component\TcaHandling\Repository\SingleDatabaseRepository;
use In2code\In2publishCore\Domain\Factory\RecordFactory;

class SysRedirectSelectDemandResolver implements DemandResolver
{
    private DualDatabaseRepository $dualDatabaseRepository;
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
        $selectRowCollection = $this->resolveSelectWithoutPropertyDemand($demands);
        $this->findMissingRecordsByUid($selectRowCollection);

        $this->createAndMapRecords($selectRowCollection, $recordCollection);
    }

    protected function resolveSelectWithoutPropertyDemand(Demands $demands): SysRedirectRowCollection
    {
        $rowCollection = new SysRedirectRowCollection();
        foreach ($demands->getSysRedirectSelect() as $table => $wheres) {
            foreach ($wheres as $where => $parentRecords) {
                try {
                    $rows = $this->dualDatabaseRepository->findByWhere($table, $where);
                } catch (Exception $exception) {
                    if ($demands instanceof CallerAwareDemandsCollection) {
                        $callers = [];
                        $meta = $demands->getMeta();
                        if (isset($meta['sysRedirectSelect'][$table][$where])) {
                            $callers = $meta['sysRedirectSelect'][$table][$where];
                        }
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
                        $row['foreign']
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
