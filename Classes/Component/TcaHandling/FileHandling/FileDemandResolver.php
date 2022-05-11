<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\FileHandling;

use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\ForeignFileInfoService;
use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\LocalFileInfoService;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Domain\Factory\RecordFactory;

class FileDemandResolver
{
    protected LocalFileInfoService $localFileInfoService;
    protected ForeignFileInfoService $foreignFileInfoService;
    protected RecordFactory $recordFactory;

    public function injectLocalFileInfoService(LocalFileInfoService $localFileInfoService): void
    {
        $this->localFileInfoService = $localFileInfoService;
    }

    public function injectForeignFileInfoService(ForeignFileInfoService $foreignFileInfoService): void
    {
        $this->foreignFileInfoService = $foreignFileInfoService;
    }

    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
    }

    public function resolveDemand(Demands $demands): RecordCollection
    {
        $recordCollection = new RecordCollection();

        $files = $demands->getFiles();

        $localFileInfo = $this->localFileInfoService->addFileInfoToFiles($files);
        $foreignFileInfo = $this->foreignFileInfoService->addFileInfoToFiles($files);

        foreach ($files as $storage => $identifiers) {
            foreach ($identifiers as $identifier => $parentRecords) {
                $localProps = $localFileInfo[$storage][$identifier]['props'] ?? [];
                $foreignProps = $foreignFileInfo[$storage][$identifier]['props'] ?? [];

                if ([] !== $localProps || [] !== $foreignProps) {
                    $record = $this->recordFactory->createFileRecord($localProps, $foreignProps);
                    foreach ($parentRecords as $parentRecord) {
                        $parentRecord->addChild($record);
                    }
                }
            }
        }
        return $recordCollection;
    }
}
