<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\FileHandling\Service\FileSystemInfoServiceInjection;
use In2code\In2publishCore\Component\Core\FileHandling\Service\ForeignFileSystemInfoServiceInjection;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;

use In2code\In2publishCore\Component\Core\RecordCollection;

use function array_keys;
use function hash;

class FileDemandResolver implements DemandResolver
{
    use RecordFactoryInjection;
    use ForeignFileSystemInfoServiceInjection;
    use FileSystemInfoServiceInjection;

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        $files = $demands->getFiles();
        if (empty($files)) {
            return;
        }

        $filesArray = [];
        foreach ($files as $storage => $identifiers) {
            foreach (array_keys($identifiers) as $identifier) {
                $filesArray[$storage][] = $identifier;
            }
        }

        $localDriverInfo = $this->fileSystemInfoService->getFileInfo($filesArray);
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
