<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactoryInjection;
use In2code\In2publishCore\Component\Core\Demand\Type\FilesInFolderDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolverInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FolderInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\MissingFileInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\ForeignFileInfoServiceInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\ForeignFolderInfoServiceInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFileInfoServiceInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFolderInfoServiceInjection;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndexInjection;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilderInjection;

use function array_keys;

class FilesInFolderDemandResolver implements DemandResolver
{
    use RecordFactoryInjection;
    use DemandResolverInjection;
    use RecordTreeBuilderInjection;
    use DemandsFactoryInjection;
    use LocalFolderInfoServiceInjection;
    use ForeignFolderInfoServiceInjection;
    use LocalFileInfoServiceInjection;
    use ForeignFileInfoServiceInjection;
    use RecordIndexInjection;

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        /** @var array<int, array<string, array<string, Record>>> $filesInFolderDemands */
        $filesInFolderDemands = $demands->getDemandsByType(FilesInFolderDemand::class);
        if (empty($filesInFolderDemands)) {
            return;
        }

        $fileDemands = $this->demandsFactory->createDemand();

        $request = [];
        foreach ($filesInFolderDemands as $storage => $parentFolderIdentifier) {
            $request[$storage] = array_keys($parentFolderIdentifier);
        }

        $localResponseCollection = $this->localFolderInfoService->getFolderInfo($request);
        $foreignResponseCollection = $this->foreignFolderInfoService->getFolderInformation($request);

        foreach ($filesInFolderDemands as $storage => $parentIdentifiers) {
            foreach ($parentIdentifiers as $parentIdentifier => $valueMap) {
                $localInfo = $localResponseCollection->getInfo($storage, $parentIdentifier);
                $foreignInfo = $foreignResponseCollection->getInfo($storage, $parentIdentifier);

                /** @var array<string, array{'local': FilesystemInfo, 'foreign': FilesystemInfo}> $mergedFiles */
                $mergedFiles = [];
                if ($localInfo instanceof FolderInfo) {
                    $localFiles = $localInfo->getFiles();
                    foreach ($localFiles as $localFile) {
                        $identifier = $localFile->getIdentifier();
                        $storage = $localFile->getStorage();
                        $mergedFiles[$identifier]['local'] = $localFile;
                        $mergedFiles[$identifier]['foreign'] = new MissingFileInfo($storage, $identifier);
                    }
                }
                if ($foreignInfo instanceof FolderInfo) {
                    $foreignFiles = $foreignInfo->getFiles();
                    foreach ($foreignFiles as $foreignFile) {
                        $identifier = $foreignFile->getIdentifier();
                        $storage = $foreignFile->getStorage();
                        $mergedFiles[$identifier]['local'] ??= new MissingFileInfo($storage, $identifier);
                        $mergedFiles[$identifier]['foreign'] = $foreignFile;
                    }
                }
                foreach ($mergedFiles as $mergedFile) {
                    $fileRecord = $this->recordFactory->createFileRecord(
                        $mergedFile['local']->toArray(),
                        $mergedFile['foreign']->toArray(),
                    );
                    if (null !== $fileRecord) {
                        $recordCollection->addRecord($fileRecord);
                        foreach ($valueMap as $record) {
                            $record->addChild($fileRecord);
                        }
                        $fileDemands->addDemand(
                            new SelectDemand(
                                'sys_file',
                                'storage = ' . $fileRecord->getProp('storage'),
                                'identifier_hash',
                                $fileRecord->getProp('identifier_hash'),
                                $fileRecord,
                            ),
                        );
                    }
                }
            }
        }

        $fileRecordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($fileDemands, $fileRecordCollection);
        $this->recordTreeBuilder->findRecordsByTca($fileRecordCollection);

        foreach ($request as $storage => $parentFolderIdentifiers) {
            foreach ($parentFolderIdentifiers as $parentFolderIdentifier) {
                /** @var FolderRecord $folderRecord */
                $folderRecord = $this->recordIndex->getRecord(
                    FolderRecord::CLASSIFICATION,
                    $storage . ':' . $parentFolderIdentifier,
                );
                $this->identifyMovedRecords($folderRecord);
            }
        }
    }

    protected function identifyMovedRecords(FolderRecord $folderRecord): void
    {
        /** @var array<string, Record> $fileRecords */
        $fileRecords = [];
        $sysFileRecordCollection = new RecordCollection();
        foreach ($folderRecord->getChildren()[FileRecord::CLASSIFICATION] ?? [] as $filesInFolder) {
            $fileRecords[$filesInFolder->getProp('identifier')] = $filesInFolder;
            $sysFileRecordCollection->addRecords($filesInFolder->getChildren()['sys_file'] ?? []);
        }

        $filesMovedOutFromFolder = [];
        $filesMovedIntoFolder = [];

        $sysFileRecords = $sysFileRecordCollection->getRecords('sys_file');
        foreach ($sysFileRecords as $sysFileRecord) {
            if ($sysFileRecord->getState() === Record::S_CHANGED) {
                $localProps = $sysFileRecord->getLocalProps();
                $foreignProps = $sysFileRecord->getForeignProps();
                $localIdentifier = $localProps['identifier'];
                $foreignIdentifier = $foreignProps['identifier'];
                if ($localIdentifier !== $foreignIdentifier) {
                    $fileRecord = $fileRecords[$localIdentifier] ?? null;
                    if (null === $fileRecord || empty($fileRecord->getLocalProps())) {
                        $filesMovedOutFromFolder[$localProps['storage']][] = $localIdentifier;
                    }
                    $fileRecord = $fileRecords[$foreignIdentifier] ?? null;
                    if (null === $fileRecord || empty($fileRecord->getForeignProps())) {
                        $filesMovedIntoFolder[$foreignProps['storage']][] = $foreignIdentifier;
                    }
                }
            }
        }

        if (!empty($filesMovedOutFromFolder)) {
            $foundMovedOutFiles = $this->localFileInfoService->getFileInfo($filesMovedOutFromFolder);
            foreach ($foundMovedOutFiles as $localInfo) {
                $identifier = $localInfo->getIdentifier();
                $fileRecords[$identifier] = $this->recordFactory->createFileRecord($localInfo->toArray(), []);
            }
        }
        if (!empty($filesMovedIntoFolder)) {
            $foundMovedIntoFiles = $this->foreignFileInfoService->getFileInfo($filesMovedIntoFolder);
            foreach ($foundMovedIntoFiles as $foreignInfo) {
                $identifier = $foreignInfo->getIdentifier();
                $fileRecords[$identifier] = $this->recordFactory->createFileRecord([], $foreignInfo->toArray());
            }
        }

        foreach ($sysFileRecords as $sysFileRecord) {
            if ($sysFileRecord->getState() === Record::S_CHANGED) {
                $localIdentifier = $sysFileRecord->getLocalProps()['identifier'];
                $foreignIdentifier = $sysFileRecord->getForeignProps()['identifier'];
                if ($localIdentifier === $foreignIdentifier) {
                    continue;
                }

                $localFileRecord = $fileRecords[$localIdentifier];
                $foreignFileRecord = $fileRecords[$foreignIdentifier];

                $folderRecord->removeChild($localFileRecord);
                $folderRecord->removeChild($foreignFileRecord);
                $localFileRecord->removeChild($sysFileRecord);
                $foreignFileRecord->removeChild($sysFileRecord);

                // Special case: A file was renamed and another file with the same name as the old one was uploaded
                if (!empty($foreignFileRecord->getLocalProps())) {
                    $newFileOnForeign = $this->recordFactory->createFileRecord(
                        $foreignFileRecord->getLocalProps(),
                        [],
                    );
                    if (null !== $newFileOnForeign) {
                        $newOnForeignSysFileRecords = $sysFileRecordCollection->getRecordsByProperties('sys_file', [
                            'storage' => $newFileOnForeign->getProp('storage'),
                            'identifier' => $newFileOnForeign->getProp('identifier'),
                        ]);
                        foreach ($newOnForeignSysFileRecords as $newOnForeignSysFileRecord) {
                            if (empty($newOnForeignSysFileRecord->getForeignProps())) {
                                $localFileRecord->removeChild($newOnForeignSysFileRecord);
                                $foreignFileRecord->removeChild($newOnForeignSysFileRecord);
                                $newFileOnForeign->addChild($newOnForeignSysFileRecord);
                            }
                        }
                        $folderRecord->addChild($newFileOnForeign);
                    }
                }
                $recordToAdd = $this->recordFactory->createFileRecord(
                    $localFileRecord->getLocalProps(),
                    $foreignFileRecord->getForeignProps(),
                );
                if (null !== $recordToAdd) {
                    $recordToAdd->addChild($sysFileRecord);
                    $folderRecord->addChild($recordToAdd);
                }
            }
        }
    }
}
