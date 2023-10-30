<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\DemandsFactoryInjection;
use In2code\In2publishCore\Component\Core\Demand\Type\FileDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolverInjection;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Event\RecordWasCreated;

class FileRecordListener
{
    use DemandsFactoryInjection;
    use DemandResolverInjection;

    /**
     * @var list<Record>
     */
    protected array $fileRecords = [];

    public function onRecordWasCreated(RecordWasCreated $event): void
    {
        $record = $event->getRecord();
        if ('sys_file' !== $record->getClassification()) {
            return;
        }
        $this->fileRecords[] = $record;
    }

    public function onRecordRelationsWereResolved(): void
    {
        if (empty($this->fileRecords)) {
            return;
        }
        $demands = $this->demandsFactory->createDemand();

        $files = $this->fileRecords;
        $this->fileRecords = [];

        foreach ($files as $record) {
            $localParentFileIds = [];
            $foreignParentFileIds = [];
            $parents = $record->getParents();
            foreach ($parents as $parent) {
                if ($parent->getClassification() === FileRecord::CLASSIFICATION) {
                    $localFileId = $parent->getLocalProps()['identifier'] ?? null;
                    if (null !== $localFileId) {
                        $localParentFileIds[$localFileId] = true;
                    }
                    $foreignFileId = $parent->getLocalProps()['identifier'] ?? null;
                    if (null !== $foreignFileId) {
                        $foreignParentFileIds[$foreignFileId] = true;
                    }
                }
            }
            $localIdentifier = $record->getLocalProps()['identifier'] ?? null;
            $localStorage = $record->getLocalProps()['storage'] ?? null;
            if (
                null !== $localStorage
                && null !== $localIdentifier
                && !isset($localParentFileIds[$localIdentifier])
            ) {
                $demands->addDemand(new FileDemand($localStorage, $localIdentifier, $record));
            }
            $foreignIdentifier = $record->getForeignProps()['identifier'] ?? null;
            $foreignStorage = $record->getForeignProps()['storage'] ?? null;
            if (
                null !== $foreignStorage
                && null !== $foreignIdentifier
                && !isset($foreignParentFileIds[$localIdentifier])
            ) {
                $demands->addDemand(new FileDemand($foreignStorage, $foreignIdentifier, $record));
            }
        }

        $recordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($demands, $recordCollection);
    }
}
