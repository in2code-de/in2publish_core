<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\FileHandling\Service\ForeignFileInfoService;
use In2code\In2publishCore\Component\Core\FileHandling\Service\LocalFileInfoService;
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

    public function resolveDemand(Demands $demands): void
    {
        $files = $demands->getFiles();

        $localFileInfo = $this->localFileInfoService->addFileInfoToFiles($files);
        $foreignFileInfo = $this->foreignFileInfoService->addFileInfoToFiles($files);

        foreach ($files as $storage => $identifiers) {
            foreach ($identifiers as $identifier => $parentRecords) {
                $localProps = $localFileInfo[$storage][$identifier]['props'] ?? [];
                $foreignProps = $foreignFileInfo[$storage][$identifier]['props'] ?? [];

                if ([] !== $localProps || [] !== $foreignProps) {
                    $record = $this->recordFactory->createFileRecord($localProps, $foreignProps);
                    if (null !== $record) {
                        foreach ($parentRecords as $parentRecord) {
                            $parentRecord->addChild($record);
                        }
                    }
                }
            }
        }
    }
}
