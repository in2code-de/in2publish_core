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

use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\FileHandling\Exception\FolderDoesNotExistOnBothSidesException;
use In2code\In2publishCore\Component\Core\FileHandling\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\FileHandling\Service\FileSystemInfoService;
use In2code\In2publishCore\Component\Core\FileHandling\Service\ForeignFileSystemInfoService;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndex;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilder;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

use function explode;
use function ltrim;
use function sha1;

class DefaultFalFinder
{
    protected ResourceFactory $resourceFactory;
    protected RecordFactory $recordFactory;
    protected FileSystemInfoService $fileSystemInfoService;
    protected ForeignFileSystemInfoService $foreignFileSystemInfoService;
    protected DemandsFactory $demandsFactory;
    protected DemandResolver $demandResolver;
    protected RecordTreeBuilder $recordTreeBuilder;
    protected FalDriverService $falDriverService;
    protected RecordIndex $recordIndex;

    public function injectResourceFactory(ResourceFactory $resourceFactory): void
    {
        $this->resourceFactory = $resourceFactory;
    }

    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
    }

    public function injectFileSystemInfoService(FileSystemInfoService $fileSystemInfoService): void
    {
        $this->fileSystemInfoService = $fileSystemInfoService;
    }

    public function injectForeignFileSystemInfoService(ForeignFileSystemInfoService $foreignFileSystemInfoService): void
    {
        $this->foreignFileSystemInfoService = $foreignFileSystemInfoService;
    }

    public function injectDemandsFactory(DemandsFactory $demandsFactory): void
    {
        $this->demandsFactory = $demandsFactory;
    }

    public function injectDemandResolver(DemandResolver $demandResolver): void
    {
        $this->demandResolver = $demandResolver;
    }

    public function injectRecordTreeBuilder(RecordTreeBuilder $recordTreeBuilder): void
    {
        $this->recordTreeBuilder = $recordTreeBuilder;
    }

    public function injectFalDriverService(FalDriverService $falDriverService): void
    {
        $this->falDriverService = $falDriverService;
    }

    public function injectRecordIndex(RecordIndex $recordIndex): void
    {
        $this->recordIndex = $recordIndex;
    }

    /**
     * Creates a Record instance representing the current chosen folder in the
     * backend module and attaches all sub folders and files as related records.
     * Also takes care of files that have not been indexed yet by FAL.
     *
     * I only work with drivers, so I don't "accidentally" index files...
     *
     * @throws FolderDoesNotExistOnBothSidesException
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
            $storageUid = $folder->getStorage()->getUid();
            $identifier = $folder->getIdentifier();
        } else {
            try {
                // This is the normal case. The local folder exists.
                $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($combinedIdentifier);
                $storageUid = $folder->getStorage()->getUid();
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (FolderDoesNotExistException $exception) {
                $localFolderExists = false;
                [$storageUid] = GeneralUtility::trimExplode(':', $combinedIdentifier);
                $storageUid = (int)$storageUid;
                $localStorage = $this->resourceFactory->getStorageObject($storageUid);
                $folder = $localStorage->getRootLevelFolder();
            }
            $identifier = GeneralUtility::trimExplode(':', $combinedIdentifier)[1];
        }
        $storageName = $folder->getStorage()->getName();

        $foreignFolderExists = $this->foreignFileSystemInfoService->folderExists($storageUid, $identifier);
        if (!$localFolderExists && !$foreignFolderExists) {
            throw new FolderDoesNotExistOnBothSidesException($combinedIdentifier, $folder->getCombinedIdentifier());
        }
        unset($folder);

        $folderName = PathUtility::basename($combinedIdentifier);
        $folderIdentifier = explode(':', $combinedIdentifier)[1];

        $localProps = [];
        if ($localFolderExists) {
            $localProps = [
                'combinedIdentifier' => $combinedIdentifier,
                'name' => $folderName ?: $storageName,
                'storage' => $storageUid,
            ];
        }

        $foreignProps = [];
        if ($foreignFolderExists) {
            $foreignProps = [
                'combinedIdentifier' => $combinedIdentifier,
                'name' => $folderName ?: $storageName,
                'storage' => $storageUid,
            ];
        }
        $folderRecord = $this->recordFactory->createFolderRecord($combinedIdentifier, $localProps, $foreignProps);
        if (null === $folderRecord) {
            return new RecordTree();
        }

        if ($onlyRoot) {
            return new RecordTree([$folderRecord]);
        }

        $localFolderContents = $this->fileSystemInfoService->listFolderContents(
            $storageUid,
            $folderIdentifier
        );
        $foreignFolderContents = $this->foreignFileSystemInfoService->listFolderContents(
            $storageUid,
            $folderIdentifier
        );

        $folders = [];
        foreach ($localFolderContents['folders'] ?? [] as $folder) {
            $combinedIdentifier = $storageUid . ':/' . ltrim($folder, '/');
            $folders[$combinedIdentifier]['local'] = [
                'combinedIdentifier' => $combinedIdentifier,
                'name' => PathUtility::basename($folder),
                'storage' => $storageUid,
            ];
        }
        foreach ($foreignFolderContents['folders'] ?? [] as $folder) {
            $combinedIdentifier = $storageUid . ':/' . ltrim($folder, '/');
            $folders[$combinedIdentifier]['foreign'] = [
                'combinedIdentifier' => $combinedIdentifier,
                'name' => PathUtility::basename($folder),
                'storage' => $storageUid,
            ];
        }
        foreach ($folders as $subFolderIdentifier => $sides) {
            $subFolderRecord = $this->recordFactory->createFolderRecord(
                $subFolderIdentifier,
                $sides['local'] ?? [],
                $sides['foreign'] ?? []
            );
            if (null === $subFolderRecord) {
                continue;
            }
            $folderRecord->addChild($subFolderRecord);
        }

        $files = [];
        foreach ($localFolderContents['files'] ?? [] as $file) {
            $files[$file['identifier']]['local'] = $file;
        }
        foreach ($foreignFolderContents['files'] ?? [] as $file) {
            $files[$file['identifier']]['foreign'] = $file;
        }
        $demands = $this->demandsFactory->createDemand();
        foreach ($files as $sides) {
            $fileRecord = $this->recordFactory->createFileRecord(
                $sides['local'] ?? [],
                $sides['foreign'] ?? []
            );
            if (null === $fileRecord) {
                continue;
            }
            $demands->addSelect(
                'sys_file',
                'storage = ' . $fileRecord->getProp('storage'),
                'identifier_hash',
                sha1($fileRecord->getProp('identifier')),
                $fileRecord
            );
            $folderRecord->addChild($fileRecord);
        }
        $recordCollection = new RecordCollection();

        $this->demandResolver->resolveDemand($demands, $recordCollection);
        $this->recordTreeBuilder->findRecordsByTca($recordCollection);

        $sysFileRecords = $recordCollection->getRecords('sys_file');
        foreach ($sysFileRecords as $sysFileRecord) {
            if ($sysFileRecord->getState() === Record::S_CHANGED) {
                $localProps = $sysFileRecord->getLocalProps();
                $foreignProps = $sysFileRecord->getForeignProps();
                $localIdentifier = $localProps['identifier'];
                $foreignIdentifier = $foreignProps['identifier'];
                if ($localIdentifier !== $foreignIdentifier) {
                    $localCombinedIdentifier = $localProps['storage'] . ':' . $localIdentifier;
                    $foreignCombinedIdentifier = $foreignProps['storage'] . ':' . $foreignIdentifier;
                    if (isset($files[$foreignIdentifier])) {
                        $files[$localIdentifier]['foreign'] = $files[$foreignIdentifier]['foreign'];
                        unset($files[$foreignIdentifier]);
                        $foreignRecordToRemove = $this->recordIndex->getRecord(
                            FileRecord::CLASSIFICATION,
                            $foreignCombinedIdentifier
                        );
                        if (null !== $foreignRecordToRemove) {
                            $folderRecord->removeChild($foreignRecordToRemove);
                        }
                        $localRecordToRemove = $this->recordIndex->getRecord(
                            FileRecord::CLASSIFICATION,
                            $localCombinedIdentifier
                        );
                        if (null !== $localRecordToRemove) {
                            $folderRecord->removeChild($localRecordToRemove);
                        }
                        $recordToAdd = $this->recordFactory->createFileRecord(
                            $files[$localIdentifier]['local'] ?? [],
                            $files[$localIdentifier]['foreign'] ?? [],
                        );
                        if (null !== $recordToAdd) {
                            $recordToAdd->addChild($sysFileRecord);
                            $folderRecord->addChild($recordToAdd);
                        }
                    }
                }
            }
        }

        return new RecordTree([$folderRecord]);
    }

    public function findFileRecord(?string $combinedIdentifier): RecordTree
    {
        [$storage, $fileIdentifier] = explode(':', $combinedIdentifier);
        $driver = $this->falDriverService->getDriver((int)$storage);

        $localProps = [];
        if ($driver->fileExists($fileIdentifier)) {
            $localProps = [
                'combinedIdentifier' => $combinedIdentifier,
                'identifier' => $fileIdentifier,
                'identifier_hash' => sha1($fileIdentifier),
                'name' => PathUtility::basename($fileIdentifier),
                'storage' => (int)$storage,
            ];
        }
        $foreignProps = [];
        if ($this->foreignFileSystemInfoService->fileExists((int)$storage, $fileIdentifier)) {
            $foreignProps = [
                'combinedIdentifier' => $combinedIdentifier,
                'identifier' => $fileIdentifier,
                'identifier_hash' => sha1($fileIdentifier),
                'name' => PathUtility::basename($fileIdentifier),
                'storage' => (int)$storage,
            ];
        }
        $fileRecord = $this->recordFactory->createFileRecord($localProps, $foreignProps);
        if (null === $fileRecord) {
            return new RecordTree();
        }

        $demands = $this->demandsFactory->createDemand();
        $demands->addSelect(
            'sys_file',
            'storage = ' . $fileRecord->getProp('storage'),
            'identifier_hash',
            sha1($fileRecord->getProp('identifier')),
            $fileRecord
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
                    if (
                        ($localIdentifier !== $foreignIdentifier)
                        && $this->foreignFileSystemInfoService->fileExists($foreignStorage, $foreignIdentifier)
                    ) {
                        $foreignProps = [
                            'combinedIdentifier' => $foreignSysFileProps['storage'] . ':' . $foreignIdentifier,
                            'identifier' => $foreignIdentifier,
                            'identifier_hash' => sha1($foreignIdentifier),
                            'name' => PathUtility::basename($foreignIdentifier),
                            'storage' => $foreignStorage,
                        ];
                        $fileRecord = $this->recordFactory->createFileRecord(
                            $localProps,
                            $foreignProps,
                        );
                        if (null === $fileRecord) {
                            return new RecordTree();
                        }
                        $fileRecord->addChild($sysFileRecord);
                    }
                }
            }
        }

        return new RecordTree([$fileRecord]);
    }
}
