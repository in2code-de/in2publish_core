<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\FalHandling\RecordFactory;

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

use In2code\In2publishCore\Component\FalHandling\RecordFactory\Factory\FileIndexFactory;
use In2code\In2publishCore\Component\RecordHandling\RecordFinder;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Utility\DatabaseUtility;
use In2code\In2publishCore\Utility\StorageDriverExtractor;
use LogicException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_diff;
use function array_intersect;
use function array_map;
use function array_merge;
use function array_values;
use function sprintf;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DefaultFolderRecordFactory implements LoggerAwareInterface, FolderRecordFactory
{
    use LoggerAwareTrait;

    protected ResourceFactory $resourceFactory;

    protected RecordFinder $recordFinder;

    protected Connection $foreignDatabase;

    protected array $configuration = [];

    protected DriverInterface $localDriver;

    protected RemoteFileAbstractionLayerDriver $foreignDriver;

    protected FileIndexFactory $fileIndexFactory;

    public function __construct(
        ResourceFactory $resourceFactory,
        RecordFinder $recordFinder,
        Connection $foreignDatabase,
        ConfigContainer $configContainer
    ) {
        $this->resourceFactory = $resourceFactory;
        $this->recordFinder = $recordFinder;
        $this->foreignDatabase = $foreignDatabase;
        $this->configuration = $configContainer->get('factory.fal');
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function initializeDependenciesAndGetFolder(?string $identifier): Folder
    {
        // Determine the current folder. If the identifier is NULL there was no folder selected.
        if (null === $identifier) {
            // Special case: The module was opened, but no storage/folder has been selected.
            // Get the default storage and the default folder to show.
            $localStorage = $this->resourceFactory->getDefaultStorage();
            // Notice: ->getDefaultFolder does not return the default folder to show, but to upload files to.
            // The root level folder is the "real" default and also respects mount points of the current user.
            $localFolder = $localStorage->getRootLevelFolder();
        } else {
            // This is the normal case. The identifier identifies the folder including its storage.
            try {
                $localFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($identifier);
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (FolderDoesNotExistException $exception) {
                [$storage] = GeneralUtility::trimExplode(':', $identifier);
                $localStorage = $this->resourceFactory->getStorageObject($storage);
                $localFolder = $localStorage->getRootLevelFolder();
            }
            $localStorage = $localFolder->getStorage();
        }

        // Get the storages driver to prevent unintentional indexing by using storage methods.
        $this->localDriver = StorageDriverExtractor::getLocalDriver($localStorage);
        $this->foreignDriver = StorageDriverExtractor::getForeignDriver($localStorage);

        $this->fileIndexFactory = GeneralUtility::makeInstance(
            FileIndexFactory::class,
            $this->localDriver,
            $this->foreignDriver
        );

        // Drop the reference to the local storage, since i've got the driver objects for both sides now.
        return $localFolder;
    }

    /**
     * Creates a Record instance representing the current chosen folder in the
     * backend module and attaches all sub folders and files as related records.
     * Also takes care of files that have not been indexed yet by FAL.
     *
     * I only work with drivers so i don't "accidentally" index files...
     */
    public function makeInstance(?string $identifier): RecordInterface
    {
        /*
         * IMPORTANT NOTICES (a.k.a "never forget about this"-Notices):
         *  1. The local folder always exist, because it's the one which has been selected (or the default)
         *  2. The foreign folder might not exist
         *  3. NEVER USE THE STORAGE, it might create new file index entries
         *  4. Blame FAL. Always.
         *  5. Lead the readers through this hell with a lot of comments ;)
         */
        $localFolder = $this->initializeDependenciesAndGetFolder($identifier);

        // Get the FAL-cleaned folder identifier (this should not be necessary, but i mistrust FAL)
        $storageUid = $localFolder->getStorage()->getUid();
        $identifier = $localFolder->getIdentifier();
        // Also get the hashed identifier, which will be used later for temporary index creation and record searching.
        $hashedIdentifier = $localFolder->getHashedIdentifier();

        // Create the main record instance. This represent the selected folder.
        $record = $this->makePhysicalFolderInstance($identifier, 1);

        // Add all related sub folders
        $record->addRelatedRecords($this->getSubFolderRecordInstances($identifier));

        // Now let's find all files inside of the selected folder by the folders hash.
        $properties = ['folder_hash' => $hashedIdentifier, 'storage' => $storageUid];
        $files = $this->recordFinder->findRecordsByProperties($properties, 'sys_file', true);

        // Identify sys_file entries with identical identifiers and add all duplicates as related record.
        $files = $this->moveSameSysFileRecordsToRelatedRecords($files);

        // FEATURE: mergeSysFileByIdentifier and enableSysFileReferenceUpdate
        if (true === $this->configuration['mergeSysFileByIdentifier']) {
            $files = $this->mergeSysFileByIdentifier($files);
        }

        $indexedIdentifiers = $this->buildIndexedIdentifiersList($files);
        $diskIdentifiers = $this->buildDiskIdentifiersList($identifier);
        $onlyDiskIdentifiers = $this->determineIdentifiersOnlyOnDisk($diskIdentifiers, $indexedIdentifiers);

        // [5] LDFF and [8] LFFD
        $this->fixIntersectingIdentifiers($diskIdentifiers, $indexedIdentifiers, $files);

        // FEATURE: reclaimSysFileEntries
        if (true === $this->configuration['reclaimSysFileEntries']) {
            foreach (['local', 'foreign'] as $side) {
                [$onlyDiskIdentifiers, $files] = $this->reclaimSysFileEntriesBySide(
                    $onlyDiskIdentifiers,
                    $hashedIdentifier,
                    $files,
                    $side
                );
            }
        }

        // [1] OLFS, [2] OFFS and [7] OFS
        $files = $this->convertAndAddOnlyDiskIdentifiersToFileRecords($onlyDiskIdentifiers, $files);

        // [10] NFDB and [13] NLDB
        $this->updateFilesWithMissingIndices($indexedIdentifiers, $diskIdentifiers, $files);

        // [0] OLDB, [3] OFDB, [4] OL, [6] ODB, [9] OF, [11] NFFS, [12] NLFS, [14] ALL and [15] NONE
        $files = $this->filterFileRecords($files);

        return $record->addRelatedRecords($files);
    }

    /**
     * Filtering:
     *  Notation:
     *      FFS = Foreign File System
     *      FDB = Foreign Database
     *      LFS = Local File System
     *      LDB = Local Database
     *  These filters files and entries i do not consider, because they do not represent an actual file.
     *  Prefer $this->localDriver over $foreignDriver where applicable, because it will be faster.
     *
     * @param RecordInterface[] $files
     *
     * @return RecordInterface[]
     * @noinspection DuplicatedCode
     * @noinspection MissingOrEmptyGroupStatementInspection
     * @noinspection PhpStatementHasEmptyBodyInspection
     * @noinspection RedundantElseClauseInspection
     */
    protected function filterFileRecords(array $files): array
    {
        $fileIdentifiers = [];
        foreach ($files as $file) {
            if ($file->hasForeignProperty('identifier')) {
                $foreignFileId = $file->getForeignProperty('identifier');
            } else {
                $foreignFileId = $file->getLocalProperty('identifier');
            }
            $fileIdentifiers[] = $foreignFileId;
        }

        // Fetch file information for all files at once to save time.
        $foreignFileExistence = $this->foreignDriver->filesExists($fileIdentifiers);

        foreach ($files as $index => $file) {
            $fdb = $file->foreignRecordExists();
            $ldb = $file->localRecordExists();

            if ($file->hasLocalProperty('identifier')) {
                $localFileId = $file->getLocalProperty('identifier');
            } else {
                $localFileId = $file->getForeignProperty('identifier');
            }
            if ($file->hasForeignProperty('identifier')) {
                $foreignFileId = $file->getForeignProperty('identifier');
            } else {
                $foreignFileId = $file->getLocalProperty('identifier');
            }

            $lfs = $this->localDriver->fileExists($localFileId);
            $ffs = $foreignFileExistence[$foreignFileId];

            if ($ldb && !$lfs && !$ffs && !$fdb) {
                // CODE: [0] OLDB; The file exists only in the local database. Ignore the orphaned DB record.
                unset($files[$index]);
                continue;
            } elseif (!$ldb && $lfs && !$ffs && !$fdb) {
                // CODE: [1] OLFS; Fixed earlier. See [4] OL
                throw new LogicException(
                    'The FAL case OLFS is impossible due to prior record transformation',
                    1475178450
                );
            } elseif (!$ldb && !$lfs && $ffs && !$fdb) {
                // CODE: [2] OFFS; Fixed earlier. See [9] OF
                throw new LogicException(
                    'The FAL case OFFS is impossible due to prior record transformation',
                    1475250513
                );
            } elseif (!$ldb && !$lfs && !$ffs && $fdb) {
                // CODE: [3] OFDB; The file exists only in the foreign database. Ignore the orphaned DB record.
                unset($files[$index]);
                continue;
            } elseif ($ldb && $lfs && !$ffs && !$fdb) {
                // CODE: [4] OL; Nothing to do here. The record exists only on local and will be displayed correctly.
            } elseif ($ldb && !$lfs && $ffs && !$fdb) {
                // CODE: [5] LDFF; Foreign disk file got indexed, local database record is ignored. See [9] OF.
                throw new LogicException(
                    'The FAL case LDFF is impossible due to prior record transformation',
                    1475252172
                );
            } elseif ($ldb && !$lfs && !$ffs && $fdb) {
                // CODE: [6] ODB; Both indices are orphaned. Ignore them. This might be a result of [12] NLFS
                unset($files[$index]);
                continue;
            } elseif (!$ldb && $lfs && $ffs && !$fdb) {
                // CODE: [7] OFS; Both disk files were indexed. See [14] ALL
                throw new LogicException(
                    'The FAL case OFS is impossible due to prior record transformation',
                    1475572486
                );
            } elseif (!$ldb && $lfs && !$ffs && $fdb) {
                // CODE: [8] LFFD. Ignored foreign database record, indexed local disk file. See [11] NFFS
                throw new LogicException(
                    'The FAL case LFFD is impossible due to prior record transformation',
                    1475573724
                );
            } elseif (!$ldb && !$lfs && $ffs && $fdb) {
                // CODE: [9] OF; Nothing to do here;
            } elseif ($ldb && $lfs && $ffs && !$fdb) {
                // CODE: [10] NFDB; Indexed the foreign file. See [14] ALL
                throw new LogicException(
                    'The FAL case NFDB is impossible due to prior record transformation',
                    1475576764
                );
            } elseif ($ldb && $lfs && !$ffs && $fdb) {
                // CODE: [11] NFFS; The foreign database record is orphaned and will be ignored.
                $file->setForeignProperties([])->setDirtyProperties()->calculateState();
            } elseif ($ldb && !$lfs && $ffs && $fdb) {
                // CODE: [12] NLFS; The local database record is orphaned and will be ignored.
                $file->setLocalProperties([])->setDirtyProperties()->calculateState();
            } elseif (!$ldb && $lfs && $ffs && $fdb) {
                // CODE: [13] NLDB; Indexed the local disk file. See [14] ALL
                throw new LogicException(
                    'The FAL case NLDB is impossible due to prior record transformation',
                    1475578482
                );
            } elseif ($ldb && $lfs && $ffs && $fdb) {
                // CODE: [14] ALL
                if (RecordInterface::RECORD_STATE_UNCHANGED === $file->getState()) {
                    // The database records are identical, but this does not necessarily reflect the reality on disk,
                    // because files might have changed in the file system without FAL noticing these changes.
                    $this->fileIndexFactory->updateFileIndexInfo($file, $localFileId, $foreignFileId);
                }
            } elseif (!$ldb && !$lfs && !$ffs && !$fdb) {
                // CODE: [15] NONE; The file exists nowhere. Ignore it.
                unset($files[$index]);
                continue;
            }
            $file->addAdditionalProperty('depth', 2);
            $file->addAdditionalProperty('isAuthoritative', true);
        }
        return $files;
    }

    /**
     * Builds a list of all index identifiers of local and foreign,
     * so files only existing on disk can be determined by diff-ing against this list
     *
     * @param RecordInterface[] $files
     *
     * @return array
     */
    protected function buildIndexedIdentifiersList(array $files): array
    {
        $indexedIdentifiers = ['local' => [], 'foreign' => [], 'both' => []];

        foreach ($files as $file) {
            $identifier = $file->getIdentifier();

            // hint: not existing properties will just return null
            $localIdentifier = $file->getLocalProperty('identifier');
            $foreignIdentifier = $file->getForeignProperty('identifier');

            // if the record was indexed on both sides
            if (null !== $localIdentifier && null !== $foreignIdentifier) {
                if ($localIdentifier === $foreignIdentifier) {
                    // if the identifiers are the same: mark the as "indexed on both sides"
                    $indexedIdentifiers['both'][$identifier] = $localIdentifier;
                } else {
                    // otherwise mark it as indexed on the respective side. this takes care of moved (renamed) files
                    $indexedIdentifiers['local'][$identifier] = $localIdentifier;
                    $indexedIdentifiers['foreign'][$identifier] = $foreignIdentifier;
                }
            } elseif (null !== $localIdentifier && null === $foreignIdentifier) {
                // only local
                $indexedIdentifiers['local'][$identifier] = $localIdentifier;
            } elseif (null === $localIdentifier && null !== $foreignIdentifier) {
                // only foreign
                $indexedIdentifiers['foreign'][$identifier] = $foreignIdentifier;
            }
        }
        return $indexedIdentifiers;
    }

    /**
     * Remove all identifiers found in the databases from the disk identifiers list to get the "disk only identifiers".
     * This list is important for any OxFS case. (local = OLFS; foreign = OFFS, both = OFS)
     */
    protected function determineIdentifiersOnlyOnDisk(array $diskIdentifiers, array $indices): array
    {
        $allIndices = array_merge($indices['local'], $indices['foreign'], $indices['both']);
        $diskIdentifiers['local'] = array_diff($diskIdentifiers['local'], $allIndices);
        $diskIdentifiers['foreign'] = array_diff($diskIdentifiers['foreign'], $allIndices);
        $diskIdentifiers['both'] = array_diff($diskIdentifiers['both'], $allIndices);
        return $diskIdentifiers;
    }

    /**
     * PRE-FIX for the [1] OLFS, [2] OFFS, [7] OFS case; Creates temporary sys_file
     * entries for all files found on exactly one or both disk and no database.
     *
     * @param array $onlyDiskIdentifiers
     * @param RecordInterface[] $files
     * @return RecordInterface[]
     */
    protected function convertAndAddOnlyDiskIdentifiersToFileRecords(array $onlyDiskIdentifiers, array $files): array
    {
        foreach (['local', 'foreign', 'both'] as $side) {
            if (!empty($onlyDiskIdentifiers[$side])) {
                // iterate through all files found on exactly one disc but not in the database
                foreach ($onlyDiskIdentifiers[$side] as $onlyDiskIdentifier) {
                    // create a temporary sys_file entry for the current identifier, since none was found or reclaimed.
                    $temporarySysFile = $this->fileIndexFactory->makeInstanceForSide($side, $onlyDiskIdentifier);
                    $files[$temporarySysFile->getIdentifier()] = $temporarySysFile;
                }
            }
        }
        return $files;
    }

    /**
     * Search on the disk for all files in the current folder and build a list of file identifiers
     * for each local and foreign, so i can identify e.g. not indexed files.
     * Move all entries occurring on both sides to the "both" index afterwards.
     *
     * The resulting array has the three keys: local, foreign and both. Therefore i know where the files were found.
     */
    protected function buildDiskIdentifiersList(string $identifier): array
    {
        $diskIdentifiers = [
            'local' => $this->getFilesIdentifiersInFolder($identifier, $this->localDriver),
            'foreign' => $this->getFilesIdentifiersInFolder($identifier, $this->foreignDriver),
            'both' => [],
        ];

        $diskIdentifiers['both'] = array_intersect($diskIdentifiers['local'], $diskIdentifiers['foreign']);
        $diskIdentifiers['local'] = array_diff($diskIdentifiers['local'], $diskIdentifiers['both']);
        $diskIdentifiers['foreign'] = array_diff($diskIdentifiers['foreign'], $diskIdentifiers['both']);

        return $diskIdentifiers;
    }

    protected function getFilesIdentifiersInFolder(string $identifier, DriverInterface $driver): array
    {
        if ($driver->folderExists($identifier)) {
            $identifierList = array_values($driver->getFilesInFolder($identifier));
            return $this->convertIdentifiers($driver, $identifierList);
        }
        return [];
    }

    /**
     * Factory method to create Record instances from a list of folder identifier
     */
    protected function getSubFolderRecordInstances(string $identifier): array
    {
        $subFolderIdentifiers = array_merge(
            $this->getSubFolderIdentifiers($this->localDriver, $identifier),
            $this->getSubFolderIdentifiers($this->foreignDriver, $identifier)
        );
        $subFolders = [];
        foreach ($subFolderIdentifiers as $subFolderIdentifier) {
            $subFolders[] = $this->makePhysicalFolderInstance($subFolderIdentifier, 2);
        }
        return $subFolders;
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    protected function makePhysicalFolderInstance(string $identifier, int $depth): RecordInterface
    {
        return GeneralUtility::makeInstance(
            Record::class,
            'physical_folder',
            $this->getFolderInfoByIdentifier($this->localDriver, $identifier),
            $this->getFolderInfoByIdentifier($this->foreignDriver, $identifier),
            [],
            ['depth' => $depth]
        );
    }

    protected function getSubFolderIdentifiers(DriverInterface $driver, string $identifier): array
    {
        if ($driver->folderExists($identifier)) {
            $identifierList = $driver->getFoldersInFolder($identifier);
            return $this->convertIdentifiers($driver, $identifierList);
        }
        return [];
    }

    /**
     * Fetches all information regarding the folder and sets the combined identifier as uid
     */
    protected function getFolderInfoByIdentifier(DriverInterface $driver, string $identifier): array
    {
        if ($driver->folderExists($identifier)) {
            $info = $driver->getFolderInfoByIdentifier($identifier);
            $info['uid'] = sprintf('%d:%s', $info['storage'], $info['identifier']);
        } else {
            $info = [];
        }
        return $info;
    }

    /** @param RecordInterface[] $files */
    protected function fixIntersectingIdentifiers(array $diskIdentifiers, array $indexedIdentifiers, array $files): void
    {
        foreach (['local' => 'foreign', 'foreign' => 'local'] as $diskSide => $indexSide) {
            // Find intersecting identifiers. These are identifiers only on one disk and the opposite database.
            $notIndexedIdentifier = array_diff(
                $diskIdentifiers[$diskSide],
                $indexedIdentifiers[$diskSide],
                $indexedIdentifiers['both']
            );
            $intersecting = array_intersect($indexedIdentifiers[$indexSide], $notIndexedIdentifier);

            foreach ($intersecting as $fileRecordUid => $identifier) {
                $file = $files[$fileRecordUid];
                $state = $file->getState();
                if ('foreign' === $diskSide && RecordInterface::RECORD_STATE_ADDED === $state) {
                    // PRE-FIX for [5] LDFF case; The file was found on the foreign disk and the local database.
                    $this->fileIndexFactory->updateFileIndexInfoBySide($file, $identifier, 'foreign', true);
                } elseif ('local' === $diskSide && RecordInterface::RECORD_STATE_DELETED === $state) {
                    // PRE-FIX for [8] LFFD case; The file exists on the local disk and the foreign database.
                    $this->fileIndexFactory->updateFileIndexInfoBySide($file, $identifier, 'local', true);
                }
            }
        }
    }

    /**
     * Reconnect sys_file entries that definitely belong to the files found
     * on disk but were not found because the folder hash is broken
     *
     * @param array $onlyDiskIdentifiers
     * @param string $hashedIdentifier
     * @param RecordInterface[] $files
     * @param string $side
     *
     * @return array
     * @throws Throwable
     * @internal param DatabaseConnection $targetDatabase
     */
    protected function reclaimSysFileEntriesBySide(
        array $onlyDiskIdentifiers,
        string $hashedIdentifier,
        array $files,
        string $side
    ): array {
        // the chance is vanishing low to find a file by its identifier in the database
        // because they should have been found by the folder hash already, but i'm a
        // generous developer and allow FAL to completely fuck up the folder hash
        foreach ($onlyDiskIdentifiers[$side] as $index => $onlyDiskIdentifier) {
            $disconnectedSysFiles = $this->recordFinder
                ->findRecordsByProperties(['identifier' => $onlyDiskIdentifier], 'sys_file');
            // if a sys_file record could be reclaimed use it
            if (!empty($disconnectedSysFiles)) {
                // repair the entry a.k.a reconnect it by updating the folder hash
                if (true === $this->configuration['autoRepairFolderHash']) {
                    foreach ($disconnectedSysFiles as $sysFileEntry) {
                        // update on the local side if record has been found on the local side.
                        // Hint: Do *not* update the foreign index with the local folder hash.
                        // The folder hash on foreign might be correctly different e.g. in case the file was moved!
                        $property = $sysFileEntry->getPropertyBySideIdentifier($side, 'folder_hash');
                        if (null !== $property) {
                            DatabaseUtility::buildDatabaseConnectionForSide($side)->update(
                                'sys_file',
                                ['uid' => (int)$sysFileEntry->getIdentifier()],
                                ['folder_hash' => $hashedIdentifier]
                            );
                            $properties = $sysFileEntry->getPropertiesBySideIdentifier($side);
                            $properties['folder_hash'] = $hashedIdentifier;
                            $sysFileEntry->setPropertiesBySideIdentifier($side, $properties);
                        }
                    }
                }
                // add the reclaimed sys_file record to the list of files
                foreach ($disconnectedSysFiles as $disconnectedSysFile) {
                    $files[$disconnectedSysFile->getIdentifier()] = $disconnectedSysFile;
                }
                // remove the identifier from the list of missing database record identifiers
                // so i can deal with them later
                unset($onlyDiskIdentifiers[$side][$index]);
            }
        }
        return [$onlyDiskIdentifiers, $files];
    }

    /**
     * mergeSysFileByIdentifier feature: Finds sys_file duplicates and "merges" them.
     *
     * If the foreign sys_file was not referenced in the foreign's sys_file_reference table the the
     * UID of the foreign record can be overwritten to restore a consistent state.
     *
     * @param RecordInterface[] $files
     *
     * @return RecordInterface[]
     */
    protected function mergeSysFileByIdentifier(array $files): array
    {
        $identifierList = [];

        foreach ($files as $file) {
            $identifier = $file->getIdentifier();
            if (null !== $localIdentifier = $file->getLocalProperty('identifier')) {
                $identifierList[$localIdentifier]['local'] = $identifier;
            }
            if (null !== $foreignIdentifier = $file->getForeignProperty('identifier')) {
                $identifierList[$foreignIdentifier]['foreign'] = $identifier;
            }
        }

        foreach ($identifierList as $identifier => $uidArray) {
            if (!isset($uidArray['local'], $uidArray['foreign']) || $uidArray['local'] === $uidArray['foreign']) {
                continue;
            }

            $foreignFile = $files[$uidArray['foreign']];
            $localFile = $files[$uidArray['local']];

            $logData = [
                'local|new' => $uidArray['local'],
                'foreign|old' => $uidArray['foreign'],
                'identifier' => $identifier,
            ];

            $localUid = (int)$uidArray['local'];
            $foreignUid = (int)$uidArray['foreign'];

            // Run the integrity test when enableSysFileReferenceUpdate (ESFRU) is not enabled
            if (true !== $this->configuration['enableSysFileReferenceUpdate']) {
                // If the sys_file was referenced abort here, because it's unsafe to overwrite the uid
                /** @noinspection NestedPositiveIfStatementsInspection */
                if (0 !== $this->countForeignReferences($foreignUid)) {
                    continue;
                }
            }

            // If a sys_file record with the "new" uid has been found abort immediately
            if (0 !== $this->countForeignIndices($localUid)) {
                // TODO: UID FLIP CANDIDATES
                continue;
            }
            try {
                // Rewrite the foreign UID of the foreign index.
                $this->updateForeignIndex($foreignUid, $localUid);
                $this->logger->notice('Rewrote a sys_file uid by the mergeSysFileByIdentifier feature', $logData);

                // Rewrite all occurrences of the old uid by the new in all references on foreign if SFRU is enabled
                if (true === $this->configuration['enableSysFileReferenceUpdate']) {
                    try {
                        $this->updateForeignReference($foreignUid, $localUid);
                        $this->logger->notice('Rewrote sys_file_reference by the SFRU feature', $logData);
                    } catch (Throwable $exception) {
                        $logData['exception'] = $exception;
                        $this->logger->error('Failed to rewrite sys_file_reference by the SFRU feature', $logData);
                        unset($logData['exception']);
                    }
                }

                // copy the foreign's properties with the new uid to the local record (merge)
                $foreignProperties = $foreignFile->getForeignProperties();
                $foreignProperties['uid'] = (string)$uidArray['local'];
                $localFile->setForeignProperties($foreignProperties);
                $localFile->setDirtyProperties()->calculateState();

                // remove the (old) foreign file from the list
                unset($files[$uidArray['foreign']]);
            } catch (Throwable $exception) {
                $logData['exception'] = $exception;
                $this->logger->error(
                    'Failed to rewrite a sys_file uid by the mergeSysFileByIdentifier feature',
                    $logData
                );
                unset($logData['exception']);
            }
        }

        return $files;
    }

    /**
     * Condition method:
     *  The sys_file exists on local, matches any identifier from the list but is not the already persisted.
     *  (UIDs are different, identifier is the same, record is not temporary)
     *
     * @param array $identifierList
     * @param RecordInterface $file
     *
     * @return bool
     */
    protected function isLocalIndexWithMatchingDuplicateIndexOnForeign(
        array $identifierList,
        RecordInterface $file
    ): bool {
        return null !== ($localIdentifier = $file->getLocalProperty('identifier'))
               && null !== ($localUid = $file->getLocalProperty('uid'))
               // There is a foreign record with the same identifier
               && isset($identifierList[$localIdentifier])
               // But there is no foreign record with the same identifier AND the same uid
               && !isset($identifierList[$localIdentifier][$localUid])
               // The foreign part of the local record does not exists OR is temporary (= Not index for a remote file)
               && !$file->foreignRecordExists();
    }

    /**
     * @param array $indexedIdentifiers
     * @param array $diskIdentifiers
     * @param RecordInterface[] $files
     */
    protected function updateFilesWithMissingIndices(
        array $indexedIdentifiers,
        array $diskIdentifiers,
        array $files
    ): void {
        // Get a list of all identifiers that exist on both disks but only in one database
        $indicesToRecheck = array_intersect($indexedIdentifiers['local'], $diskIdentifiers['both'])
                            + array_intersect($indexedIdentifiers['foreign'], $diskIdentifiers['both']);

        foreach ($indicesToRecheck as $index => $identifier) {
            $file = $files[$index];
            $recordState = $file->getState();
            if (RecordInterface::RECORD_STATE_ADDED === $recordState) {
                // PRE-FIX for [10] NFDB; The file has been found on both file systems but not in the foreign database.
                // Create a temporary counterpart for the local index, so we end up in [14] ALL
                $this->fileIndexFactory->updateFileIndexInfoBySide($file, $identifier, 'foreign');
            } elseif (RecordInterface::RECORD_STATE_DELETED === $recordState) {
                // PRE-FIX for [13] NLDB; The file has been found on both file systems but not in the local database.
                // Create a temporary local index with the uid of the existing foreign index. Results is [14] ALL
                $this->fileIndexFactory->updateFileIndexInfoBySide($file, $identifier, 'local');
            }
        }
    }

    protected function updateForeignIndex(int $oldUid, int $newUid): void
    {
        $this->foreignDatabase->update('sys_file', ['uid' => $newUid], ['uid' => $oldUid]);
    }

    protected function updateForeignReference(int $oldUid, int $newUid): void
    {
        $this->foreignDatabase->update(
            'sys_file_reference',
            ['uid' => $newUid],
            ['uid_local' => $oldUid, 'table_local' => 'sys_file']
        );
    }

    protected function countForeignReferences(int $oldUid): int
    {
        $query = $this->foreignDatabase->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->count('*')
              ->from('sys_file_reference')
              ->where($query->expr()->eq('table_local', $query->createNamedParameter('sys_file')))
              ->andWhere($query->expr()->eq('uid_local', $query->createNamedParameter($oldUid)));
        try {
            $result = $query->execute();
            $count = $result->fetchOne();
        } catch (Throwable $exception) {
            $this->logger->critical(
                'Could not count foreign references by uid',
                ['uid' => $oldUid, 'exception' => $exception]
            );
            throw new In2publishCoreException('Could not count foreign references by uid', 1476097402, $exception);
        }
        return (int)$count;
    }

    protected function countForeignIndices(int $newUid): int
    {
        $query = $this->foreignDatabase->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->count('uid')
              ->from('sys_file')
              ->where($query->expr()->eq('uid', $query->createNamedParameter($newUid)));
        try {
            $result = $query->execute();
            $count = $result->fetchOne();
        } catch (Throwable $exception) {
            $this->logger->critical(
                'Could not count foreign indices by uid',
                ['uid' => $newUid, 'exception' => $exception]
            );
            throw new In2publishCoreException('Could not count foreign indices by uid', 1476097373, $exception);
        }
        return (int)$count;
    }

    protected function convertIdentifiers(DriverInterface $driver, array $identifierList): array
    {
        if (!$driver->isCaseSensitiveFileSystem()) {
            return array_map('strtolower', $identifierList);
        }
        return $identifierList;
    }

    /**
     * TYPO3 may create more than one sys_file record for an actual file (or different versions of it).
     * Each sys_file record will be shown as an independent record in the publish files module.
     * To prevent these duplications, we take every sys_file record which has an identical identifier as any previous
     * record and attach that record as a related record, so they will be published as one.
     *
     * @param RecordInterface[] $files
     * @return RecordInterface[]
     */
    protected function moveSameSysFileRecordsToRelatedRecords(array $files): array
    {
        /** @var RecordInterface[] $fileRecords */
        $fileRecords = [];
        foreach ($files as $idx => $file) {
            $localIdentifier = $file->getLocalProperty('identifier');
            if (isset($fileRecords[$localIdentifier])) {
                $fileRecords[$localIdentifier]->addRelatedRecord($file);
                unset($files[$idx]);
            } else {
                $fileRecords[$localIdentifier] = $file;
            }
        }
        return $files;
    }
}
