<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\CommonInjection\EventDispatcherInjection;
use In2code\In2publishCore\CommonInjection\ResourceFactoryInjection;
use In2code\In2publishCore\Component\Core\Demand\DemandBuilderInjection;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactoryInjection;
use In2code\In2publishCore\Component\Core\Demand\Type\FilesInFolderDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\FolderDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\FoldersInFolderDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolverInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FileInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\ForeignFileInfoServiceInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFileInfoServiceInjection;
use In2code\In2publishCore\Component\Core\FileHandling\Exception\FolderDoesNotExistOnBothSidesException;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilderInjection;
use In2code\In2publishCore\Event\RecordRelationsWereResolved;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function explode;
use function str_contains;
use function trim;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DefaultFalFinder
{
    use RecordFactoryInjection;
    use DemandsFactoryInjection;
    use DemandResolverInjection;
    use ResourceFactoryInjection;
    use RecordTreeBuilderInjection;
    use EventDispatcherInjection;
    use LocalFileInfoServiceInjection;
    use ForeignFileInfoServiceInjection;
    use DemandBuilderInjection;

    /**
     * Creates a Record instance representing the current chosen folder in the
     * backend module and attaches all sub folders and files as related records.
     * Also takes care of files that have not been indexed yet by FAL.
     *
     * I only work with drivers, so I don't "accidentally" index files...
     *
     * @throws FolderDoesNotExistOnBothSidesException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findFolderRecord(?string $combinedIdentifier, bool $onlyRoot = false): RecordTree
    {
        /*
         * IMPORTANT NOTICES (a.k.a. "never forget about this"-Notices):
         *  1. The local folder might not exist anymore, because the combinedIdentifier is persisted in the session.
         *  2. The foreign folder might not exist
         *  3. NEVER USE THE STORAGE, it might create new file index entries
         *  4. Blame FAL. Always.
         *  5. Do not search for sys_file records. Only find files on disks, then attach sys_file records to them.
         */

        $localFolderExists = true;
        // Determine the current folder. If the identifier is NULL there was no folder selected.
        if (null === $combinedIdentifier) {
            // Special case: The module was opened, but no storage/folder has been selected.
            // Get the default storage and the default folder to show.
            // Notice: ->getDefaultFolder does not return the default folder to show, but to upload files to.
            // The root level folder is the "real" default and also respects mount points of the current user.
            $folder = $this->resourceFactory->getDefaultStorage()->getRootLevelFolder();
            // Update the combinedIdentifier to the actual folder we work with.
            $combinedIdentifier = $folder->getCombinedIdentifier();
            $storage = $folder->getStorage()->getUid();
            $identifier = $folder->getIdentifier();
        } else {
            $combinedIdentifier = $this->normalizeCombinedIdentifier($combinedIdentifier);
            try {
                // This is the normal case. The local folder exists.
                $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($combinedIdentifier);
                $storage = $folder->getStorage()->getUid();
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (FolderDoesNotExistException $exception) {
                [$storage] = GeneralUtility::trimExplode(':', $combinedIdentifier);
                $storage = (int)$storage;
                $localStorage = $this->resourceFactory->getStorageObject($storage);
                $folder = $localStorage->getRootLevelFolder();
            }
            $identifier = GeneralUtility::trimExplode(':', $combinedIdentifier)[1];
        }

        $recordTree = new RecordTree();

        $demands = $this->demandsFactory->createDemand();
        $demands->addDemand(new FolderDemand($storage, $identifier, $recordTree));

        $recordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($demands, $recordCollection);

        $demands = $this->demandBuilder->buildDemandForRecords($recordCollection);
        $recordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($demands, $recordCollection);

        $folderRecord = $recordTree->getChild(FolderRecord::CLASSIFICATION, $combinedIdentifier);
        if (null === $folderRecord) {
            throw new FolderDoesNotExistOnBothSidesException($combinedIdentifier, $folder->getCombinedIdentifier());
        }

        if ($onlyRoot) {
            $this->eventDispatcher->dispatch(new RecordRelationsWereResolved($recordTree));
            return $recordTree;
        }

        $demands = $this->demandsFactory->createDemand();
        $demands->addDemand(new FoldersInFolderDemand($storage, $identifier, $folderRecord));
        $demands->addDemand(new FilesInFolderDemand($storage, $identifier, $folderRecord));

        $recordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($demands, $recordCollection);

        $this->eventDispatcher->dispatch(new RecordRelationsWereResolved($recordTree));

        return $recordTree;
    }

    public function findFileRecord(?string $combinedIdentifier): RecordTree
    {
        [$storage, $fileIdentifier] = explode(':', $combinedIdentifier);
        $storage = (int)$storage;
        $request = [$storage => [$fileIdentifier]];

        $localFileInfo = $this->localFileInfoService->getFileInfo($request);
        $fileInformation = $localFileInfo->getInfo($storage, $fileIdentifier);
        $localProps = $fileInformation->toArray();

        $foreignFileInfo = $this->foreignFileInfoService->getFileInfo($request);
        $fileInformation = $foreignFileInfo->getInfo($storage, $fileIdentifier);
        $foreignProps = $fileInformation->toArray();

        $fileRecord = $this->recordFactory->createFileRecord($localProps, $foreignProps);
        if (null === $fileRecord) {
            return new RecordTree();
        }

        $demands = $this->demandsFactory->createDemand();
        $demands->addDemand(
            new SelectDemand(
                'sys_file',
                'storage = ' . $fileRecord->getProp('storage'),
                'identifier_hash',
                $fileRecord->getProp('identifier_hash'),
                $fileRecord,
            ),
        );
        $recordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($demands, $recordCollection);
        $this->recordTreeBuilder->findRecordsByTca($recordCollection);

        if ($foreignProps === []) {
            $sysFileRecords = $recordCollection->getRecords('sys_file');
            foreach ($sysFileRecords as $sysFileRecord) {
                if ($sysFileRecord->getState() === Record::S_CHANGED) {
                    $localSysFileProps = $sysFileRecord->getLocalProps();
                    $foreignSysFileProps = $sysFileRecord->getForeignProps();
                    $localIdentifier = $localSysFileProps['identifier'];
                    $foreignIdentifier = $foreignSysFileProps['identifier'];
                    $foreignStorage = (int)$foreignSysFileProps['storage'];
                    if ($localIdentifier !== $foreignIdentifier) {
                        $fileInfoCollection = $this->foreignFileInfoService->getFileInfo(
                            [$foreignStorage => [$foreignIdentifier]],
                        );
                        $fileInfo = $fileInfoCollection->getInfo($foreignStorage, $foreignIdentifier);
                        if ($fileInfo instanceof FileInfo) {
                            $foreignProps = $fileInfo->toArray();
                            $fileRecord = $this->recordFactory->createFileRecord($localProps, $foreignProps);
                            if (null === $fileRecord) {
                                return new RecordTree();
                            }
                            $fileRecord->addChild($sysFileRecord);
                        }
                    }
                }
            }
        }

        $recordTree = new RecordTree([$fileRecord]);

        $this->eventDispatcher->dispatch(new RecordRelationsWereResolved($recordTree));

        return $recordTree;
    }

    protected function normalizeCombinedIdentifier(string $combinedIdentifier): string
    {
        if (str_contains($combinedIdentifier, ':')) {
            [$storage, $name] = explode(':', $combinedIdentifier);
            $combinedIdentifier = (int)$storage . ':/' . trim($name, '/');
        }
        return $combinedIdentifier;
    }
}
