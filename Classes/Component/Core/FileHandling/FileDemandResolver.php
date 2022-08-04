<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\FileHandling\Service\ForeignFileSystemInfoService;
use In2code\In2publishCore\Component\Core\FileHandling\Service\LocalFileInfoService;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;

use function array_keys;
use function hash;

class FileDemandResolver
{
    protected LocalFileInfoService $localFileInfoService;
    protected RecordFactory $recordFactory;
    protected ForeignFileSystemInfoService $foreignFileSystemInfoService;

    public function injectLocalFileInfoService(LocalFileInfoService $localFileInfoService): void
    {
        $this->localFileInfoService = $localFileInfoService;
    }

    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
    }

    public function injectForeignFileSystemInfoService(ForeignFileSystemInfoService $foreignFileSystemInfoService): void
    {
        $this->foreignFileSystemInfoService = $foreignFileSystemInfoService;
    }

    public function resolveDemand(Demands $demands): void
    {
        $files = $demands->getFiles();

        $filesArray = [];
        foreach ($files as $storage => $identifiers) {
            foreach (array_keys($identifiers) as $identifier) {
                $filesArray[$storage][] = $identifier;
            }
        }

        $localDriverInfo = $this->localFileInfoService->getFileInfo($filesArray);
        $localFileInfo = $this->addFileInfoToDriverInfo($files, $localDriverInfo);
        $foreignDriverInfo = $this->foreignFileSystemInfoService->getFileInfo($filesArray);
        $foreignFileInfo = $this->addFileInfoToDriverInfo($files, $foreignDriverInfo);

        foreach ($files as $storage => $identifiers) {
            foreach ($identifiers as $identifier => $parentRecords) {
                $localProps = $localFileInfo[$storage][$identifier] ?? [];
                $foreignProps = $foreignFileInfo[$storage][$identifier] ?? [];

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

    protected function addFileInfoToDriverInfo(array $files, array $driverInfo): array
    {
        $result = [];
        foreach ($files as $storage => $identifiers) {
            foreach ($identifiers as $identifier => $parentRecords) {
                if (isset($driverInfo[$storage][$identifier])) {
                    $fileInfo = $driverInfo[$storage][$identifier];
                    $result[$storage][$identifier] = [
                        'storage' => $storage,
                        'identifier' => $identifier,
                        'identifier_hash' => hash('sha1', $identifier),
                        'size' => $fileInfo['size'],
                        'mimetype' => $fileInfo['mimetype'],
                        'name' => $fileInfo['name'],
                        'extension' => $fileInfo['extension'],
                        'folder_hash' => $fileInfo['folder_hash'],
                    ];
                }
            }
        }
        return $result;
    }
}
