<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Event\RecordWasCreated;

class FileRecordListener
{
    protected FileDemandResolver $fileDemandResolver;
    protected DemandsFactory $demandsFactory;
    /**
     * @var list<Record>
     */
    protected array $fileRecords = [];

    public function injectFileDemandResolver(FileDemandResolver $fileDemandResolver): void
    {
        $this->fileDemandResolver = $fileDemandResolver;
    }

    public function injectDemandsFactory(DemandsFactory $demandsFactory): void
    {
        $this->demandsFactory = $demandsFactory;
    }

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
            $localIdentifier = $record->getLocalProps()['identifier'] ?? null;
            $localStorage = $record->getLocalProps()['storage'] ?? null;
            if (null !== $localStorage && null !== $localIdentifier) {
                $demands->addFile($localStorage, $localIdentifier, $record);
            }
            $foreignIdentifier = $record->getForeignProps()['identifier'] ?? null;
            $foreignStorage = $record->getForeignProps()['storage'] ?? null;
            if (null !== $foreignStorage && null !== $foreignIdentifier) {
                $demands->addFile($foreignStorage, $foreignIdentifier, $record);
            }
        }

        $this->fileDemandResolver->resolveDemand($demands);
    }
}
