<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\FoldersInFolderDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FolderInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\MissingFolderInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\ForeignFolderInfoServiceInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFolderInfoServiceInjection;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndexInjection;

use function array_keys;

class FoldersInFolderDemandResolver implements DemandResolver
{
    use RecordIndexInjection;
    use RecordFactoryInjection;
    use LocalFolderInfoServiceInjection;
    use ForeignFolderInfoServiceInjection;

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        /** @var array<int, array<string, array<string, Record>>> $foldersInFolderDemand */
        $foldersInFolderDemand = $demands->getDemandsByType(FoldersInFolderDemand::class);
        if (empty($foldersInFolderDemand)) {
            return;
        }

        $request = [];
        foreach ($foldersInFolderDemand as $storage => $parentFolderIdentifier) {
            $request[$storage] = array_keys($parentFolderIdentifier);
        }

        $localResponseCollection = $this->localFolderInfoService->getFolderInfo($request);
        $foreignResponseCollection = $this->foreignFolderInfoService->getFolderInformation($request);

        foreach ($foldersInFolderDemand as $storage => $parentIdentifiers) {
            foreach ($parentIdentifiers as $parentIdentifier => $valueMap) {
                $localInfo = $localResponseCollection->getInfo($storage, $parentIdentifier);
                $foreignInfo = $foreignResponseCollection->getInfo($storage, $parentIdentifier);

                /** @var array<string, array{'local': FilesystemInfo, 'foreign': FilesystemInfo}> $mergedFolders */
                $mergedFolders = [];
                if ($localInfo instanceof FolderInfo) {
                    $localFolders = $localInfo->getFolders();
                    foreach ($localFolders as $localFolder) {
                        $identifier = $localFolder->getIdentifier();
                        $storage = $localFolder->getStorage();
                        $mergedFolders[$identifier]['local'] = $localFolder;
                        $mergedFolders[$identifier]['foreign'] = new MissingFolderInfo($storage, $identifier);
                    }
                }
                if ($foreignInfo instanceof FolderInfo) {
                    $foreignFolders = $foreignInfo->getFolders();
                    foreach ($foreignFolders as $foreignFolder) {
                        $identifier = $foreignFolder->getIdentifier();
                        $storage = $foreignFolder->getStorage();
                        $mergedFolders[$identifier]['local'] ??= new MissingFolderInfo($storage, $identifier);
                        $mergedFolders[$identifier]['foreign'] = $foreignFolder;
                    }
                }
                foreach ($mergedFolders as $mergedFolder) {
                    $folderRecord = $this->recordFactory->createFolderRecord(
                        $mergedFolder['local']->toArray(),
                        $mergedFolder['foreign']->toArray(),
                    );
                    if (null !== $folderRecord) {
                        foreach ($valueMap as $record) {
                            $record->addChild($folderRecord);
                        }
                    }
                }
            }
        }
    }
}
