<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\FileDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\ForeignFileInfoServiceInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFileInfoServiceInjection;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;
use In2code\In2publishCore\Component\Core\RecordCollection;

use function array_keys;

class FileDemandResolver implements DemandResolver
{
    use RecordFactoryInjection;
    use LocalFileInfoServiceInjection;
    use ForeignFileInfoServiceInjection;

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        $files = $demands->getDemandsByType(FileDemand::class);
        if (empty($files)) {
            return;
        }

        $filesArray = [];
        foreach ($files as $storage => $identifiers) {
            foreach (array_keys($identifiers) as $identifier) {
                $filesArray[$storage][] = $identifier;
            }
        }

        $localDriverInfo = $this->localFileInfoService->getFileInfo($filesArray);
        $localFileInfo = $this->addFileInfoToDriverInfo($files, $localDriverInfo);
        $foreignDriverInfo = $this->foreignFileInfoService->getFileInfo($filesArray);
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

    protected function addFileInfoToDriverInfo(array $files, FilesystemInformationCollection $driverInfo): array
    {
        $result = [];
        foreach ($files as $storage => $identifiers) {
            foreach ($identifiers as $identifier => $parentRecords) {
                $fileInfo = $driverInfo->getInfo($storage, $identifier);
                $result[$storage][$identifier] = $fileInfo->toArray();
            }
        }
        return $result;
    }
}
