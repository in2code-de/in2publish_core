<?php
namespace In2code\In2publishCore\Domain\Factory;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Reflection\PropertyReflection;

/**
 * Class FolderRecordFactory
 */
class FolderRecordFactory
{
    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var array
     */
    protected $configuration = array();

    /**
     * FolderRecordFactory constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
        $this->configuration = ConfigurationUtility::getConfiguration('factory.fal');
    }

    /**
     * Creates a Record instance representing the current chosen folder in the
     * backend module and attaches all sub folders and files as related records.
     * Also takes care of files that have not been indexed yet by FAL.
     *
     * Only work with drivers so we don't "accidentally" index files...
     *
     * Variable naming rules:
     *  $identifier is the identifier of the chosen folder and must not be used for any other purpose
     *
     * @param string|null $identifier
     * @return Record
     */
    public function makeInstance($identifier)
    {
        /*
         * IMPORTANT NOTICES (a.k.a "never forget about this"-Notices):
         *  1. The local folder always exist, because it's the one which has been selected (or the default)
         *  2. The foreign folder might not exist
         *  3. NEVER USE THE STORAGE, it might create new file index entries
         *  4. Blame FAL. Always.
         *  5. Lead the readers through this hell with a lot of comments ;)
         */
        $resourceFactory = ResourceFactory::getInstance();

        // determine the current folder
        if (null === $identifier) {
            // Special case: The module was opened, but no storage/folder has been selected
            $localStorage = $resourceFactory->getDefaultStorage();
            // root level folder is the "real" default and respects mount points
            $localFolder = $localStorage->getRootLevelFolder();
        } else {
            $localFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier($identifier);
            $localStorage = $localFolder->getStorage();
        }

        // get the storages driver to prevent unintentional indexing
        $localDriver = $this->getLocalDriver($localStorage);
        $foreignDriver = $this->getForeignDriver($localStorage);

        // fetch all information regarding the folder on this side
        $identifier = $localFolder->getIdentifier();
        $localFolderInfo = $localDriver->getFolderInfoByIdentifier($identifier);
        // add the "uid" property, which is largely exclusive set for Record::isRecordRepresentByProperties
        // but additionally a good place to store the "combined identifier"
        $localFolderInfo['uid'] = $this->createCombinedIdentifier($localFolderInfo);

        // retrieve all local sub folder identifiers (no recursion! no database!)
        // these are not Record instances, yet!
        $localSubFolders = $localDriver->getFoldersInFolder($identifier);

        // do the same on foreign, if the currently selected folder exists on foreign
        if ($foreignDriver->folderExists($localFolder->getIdentifier())) {
            // as you can see these lines are the same as above, the driver is just another one
            $foreignFolderInfo = $foreignDriver->getFolderInfoByIdentifier($localFolder->getIdentifier());
            $foreignFolderInfo['uid'] = $this->createCombinedIdentifier($foreignFolderInfo);
            $remoteSubFolders = $foreignDriver->getFoldersInFolder($identifier);
        } else {
            // otherwise just set "empty" values to flag the folder "record" as non existent
            $foreignFolderInfo = array();
            $remoteSubFolders = array();
        }

        // finally create a Record instance representing the selected folder
        $record = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Model\\Record',
            'physical_folder',
            $localFolderInfo,
            $foreignFolderInfo,
            array(),
            array('depth' => 1)
        );

        // create Record instances from the sub folder identifier lists
        $subFolders = $this->getSubFolderRecordInstances(
            array_merge($localSubFolders, $remoteSubFolders),
            $localDriver,
            $foreignDriver
        );

        // add all sub folder Records
        $record->addRelatedRecords($subFolders);

        // clean up a bit
        unset($resourceFactory);
        unset($localStorage);
        unset($localFolderInfo);
        unset($localSubFolders);
        unset($foreignFolderInfo);
        unset($remoteSubFolders);
        unset($subFolders);

        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();

        // Now let's find all files in the selected folder
        // Get the Repo first
        $commonRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get(
            'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
            $localDatabase,
            $foreignDatabase,
            'sys_file'
        );

        // find all file database entries in the current folder by the folder's hash
        // (be sure to only use FAL methods for hashing)
        $folderHash = $localFolder->getHashedIdentifier();
        $files = $commonRepository->findByProperty('folder_hash', $folderHash);

        // list all identifiers of database entry files in the current folder
        $identifierList = $this->buildIdentifiersList($files);

        // get all file identifiers of files actually existing in the current folder but not in the database
        $localFileIdentifiers = array_values($localDriver->getFilesInFolder($identifier));

        // find all files which are not indexed (don't care of files in DB but not in FS)
        $onlyLocalFileSystemFileIdentifiers = array_diff($localFileIdentifiers, $identifierList);

        if ($foreignDriver->folderExists($identifier)) {
            $foreignFileIdentifiers = array_values($foreignDriver->getFilesInFolder($identifier));
            $onlyForeignFileSystemFileIdentifiers = array_diff($foreignFileIdentifiers, $identifierList);
        } else {
            $foreignFileIdentifiers = array();
            $onlyForeignFileSystemFileIdentifiers = array();
        }

        // PRE-FIX for [7] OFS case. Files exist on both disks, but in neither of the databases.
        // Move those entries to a third array to deal with them separately
        $onlyFileSystemEntries = array_intersect($onlyLocalFileSystemFileIdentifiers, $onlyForeignFileSystemFileIdentifiers);
        $onlyLocalFileSystemFileIdentifiers = array_diff($onlyLocalFileSystemFileIdentifiers, $onlyFileSystemEntries);
        $onlyForeignFileSystemFileIdentifiers = array_diff($onlyForeignFileSystemFileIdentifiers, $onlyFileSystemEntries);

        if (!empty($onlyFileSystemEntries)) {
            // iterate through all files found on the local and foreign disk but not in the database
            foreach ($onlyFileSystemEntries as $index => $fileSystemEntryIdentifier) {
                static $tcaService = null;
                if (null === $tcaService) {
                    $tcaService = GeneralUtility::makeInstance(
                        'In2code\\In2publishCore\\Service\\Configuration\\TcaService'
                    );
                }
                // fetch the file information with a reserved uid
                $localFileInformation = $this->getFileInformation(
                    $fileSystemEntryIdentifier,
                    $localDriver,
                    $foreignDatabase,
                    $localDatabase
                );
                $temporarySysFile = GeneralUtility::makeInstance(
                    'In2code\\In2publishCore\\Domain\\Model\\Record',
                    'sys_file',
                    $localFileInformation,
                    // assign the already reserved uid to the foreignFileInformation
                    $this->getFileInformation(
                        $fileSystemEntryIdentifier,
                        $foreignDriver,
                        $localDatabase,
                        $foreignDatabase,
                        $localFileInformation['uid']
                    ),
                    $tcaService->getConfigurationArrayForTable('sys_file'),
                    array('localRecordExistsTemporary' => true, 'foreignRecordExistsTemporary' => true)
                );
                $files[$temporarySysFile->getIdentifier()] = $temporarySysFile;
                unset($onlyFileSystemEntries[$index]);
            }
        }

        if (!empty($onlyFileSystemEntries)) {
            throw new \RuntimeException('Failed to convert all disk-only files to records', 1475253143);
        }


        // PRE-FIX for [5] LDFF case, where the file was found on foreign's disk and the local database
        // Short: Removes LDB but adds FDB instead. Results in OF
        $fileRecordsToRecheck = array_intersect($identifierList, $foreignFileIdentifiers);
        foreach ($fileRecordsToRecheck as $fileRecordUid => $reCheckIdentifier) {
            $reCheckFile = $files[$fileRecordUid];
            // The database record is technically added, but the file was removed. Since the file publishing is the
            // main domain of this class the state of the file on disk has precedence
            if (RecordInterface::RECORD_STATE_ADDED === $reCheckFile->getState()) {
                if (!$localDriver->fileExists($reCheckIdentifier)) {
                    // remove all local properties to "ignore" the local database record
                    $reCheckFile->setLocalProperties(array());
                    // add foreign file information instead
                    $reCheckFile->setForeignProperties(
                        $this->getFileInformation(
                            $reCheckIdentifier,
                            $foreignDriver,
                            $localDatabase,
                            $foreignDatabase
                        )
                    );
                    $reCheckFile->addAdditionalProperty('foreignRecordExistsTemporary', true);
                    // TODO: trigger the following inside the record itself so it can't be forgotten
                    $reCheckFile->setDirtyProperties()->calculateState();
                }
            }
        }

        // Reconnect sys_file entries that definitely belong to the files found on disk but were not found because
        // the folder hash is broken
        if (true === $this->configuration['reclaimSysFileEntries']) {
            // the chance is vanishing low to find a file by its identifier in the database
            // because they should have been found by the folder hash already, but i'm a
            // generous developer and allow FAL to completely fuck up the folder hash
            foreach ($onlyLocalFileSystemFileIdentifiers as $index => $localFileSystemFileIdentifier) {
                $disconnectedSysFiles = $commonRepository->findByProperty('identifier', $localFileSystemFileIdentifier);
                // if a sys_file record could be reclaimed use it
                if (!empty($disconnectedSysFiles)) {
                    // repair the entry a.k.a reconnect it by updating the folder hash
                    if (true === $this->configuration['autoRepairFolderHash']) {
                        foreach ($disconnectedSysFiles as $sysFileEntry) {
                            // No need to check if this entry belongs to another file, since the folder hash was wrong
                            // but the identifier was 100% correct
                            $uid = $sysFileEntry->getIdentifier();
                            // update on the local side if record has been found on the local side.
                            // Hint: Do *not* update foreign. The folder hash on foreign might be correctly different
                            // e.g. in case the file was moved
                            if ($sysFileEntry->hasLocalProperty('folder_hash')) {
                                $localDatabase->exec_UPDATEquery(
                                    'sys_file',
                                    'uid=' . $uid,
                                    array('folder_hash' => $folderHash)
                                );
                                $localProperties = $sysFileEntry->getLocalProperties();
                                $localProperties['folder_hash'] = $folderHash;
                                $sysFileEntry->setLocalProperties($localProperties);
                            }
                        }
                    }
                    // add the reclaimed sys_file record to the list of files
                    foreach ($disconnectedSysFiles as $disconnectedSysFile) {
                        $files[$disconnectedSysFile->getIdentifier()] = $disconnectedSysFile;
                    }
                    // remove the identifier from the list of missing database record identifiers
                    // so we can deal with them later
                    unset($onlyLocalFileSystemFileIdentifiers[$index]);
                }
            }

            foreach ($onlyForeignFileSystemFileIdentifiers as $index => $foreignFileSystemFileIdentifier) {
                $disconnectedSysFiles = $commonRepository->findByProperty(
                    'identifier',
                    $foreignFileSystemFileIdentifier
                );
                // if a sys_file record could be reclaimed use it
                if (!empty($disconnectedSysFiles)) {
                    // repair the entry a.k.a reconnect it by updating the folder hash
                    if (true === $this->configuration['autoRepairFolderHash']) {
                        foreach ($disconnectedSysFiles as $sysFileEntry) {
                            // No need to check if this entry belongs to another file, since the folder hash was wrong
                            // but the identifier was 100% correct
                            $uid = $sysFileEntry->getIdentifier();
                            // update on the local side if record has been found on the local side.
                            // Hint: Do *not* update foreign. The folder hash on foreign might be correctly different
                            // e.g. in case the file was moved
                            if ($sysFileEntry->hasForeignProperty('folder_hash')) {
                                $foreignDatabase->exec_UPDATEquery(
                                    'sys_file',
                                    'uid=' . $uid,
                                    array('folder_hash' => $folderHash)
                                );
                                $localProperties = $sysFileEntry->getForeignProperties();
                                $localProperties['folder_hash'] = $folderHash;
                                $sysFileEntry->setForeignProperties($localProperties);
                            }
                        }
                    }
                    // add the reclaimed sys_file record to the list of files
                    foreach ($disconnectedSysFiles as $disconnectedSysFile) {
                        $files[$disconnectedSysFile->getIdentifier()] = $disconnectedSysFile;
                    }
                    // remove the identifier from the list of missing database record identifiers
                    // so we can deal with them later
                    unset($onlyForeignFileSystemFileIdentifiers[$index]);
                }
            }
        }

        // PRE-FIX for the [1] OLFS case
        // create temporary sys_file entries for all files on the local disk
        if (!empty($onlyLocalFileSystemFileIdentifiers)) {
            // iterate through all files found on disk but not in the database
            foreach ($onlyLocalFileSystemFileIdentifiers as $index => $localFileSystemFileIdentifier) {
                static $tcaService = null;
                if (null === $tcaService) {
                    $tcaService = GeneralUtility::makeInstance(
                        'In2code\\In2publishCore\\Service\\Configuration\\TcaService'
                    );
                }
                $temporarySysFile = GeneralUtility::makeInstance(
                    'In2code\\In2publishCore\\Domain\\Model\\Record',
                    'sys_file',
                    // create a temporary sys_file entry for the current
                    // identifier, since none was found nor could be reclaimed
                    // if persistTemporaryIndexing is enabled the entry is not temporary
                    // but this does not matter for the following code
                    $this->getFileInformation(
                        $localFileSystemFileIdentifier,
                        $localDriver,
                        $foreignDatabase,
                        $localDatabase
                    ),
                    array(),
                    $tcaService->getConfigurationArrayForTable('sys_file'),
                    array('localRecordExistsTemporary' => true)
                );
                $files[$temporarySysFile->getIdentifier()] = $temporarySysFile;
                unset($onlyLocalFileSystemFileIdentifiers[$index]);
            }
        }

        if (!empty($onlyLocalFileSystemFileIdentifiers)) {
            throw new \RuntimeException('Failed to convert all local files from disk to records', 1475177184);
        }

        // PRE-FIX for the [2] OFFS case
        // create temporary sys_file entries for all files on the foreign disk
        if (!empty($onlyForeignFileSystemFileIdentifiers)) {
            // iterate through all files found on disc but not in the database
            foreach ($onlyForeignFileSystemFileIdentifiers as $index => $foreignFileSystemFileIdentifier) {
                static $tcaService = null;
                if (null === $tcaService) {
                    $tcaService = GeneralUtility::makeInstance(
                        'In2code\\In2publishCore\\Service\\Configuration\\TcaService'
                    );
                }
                $temporarySysFile = GeneralUtility::makeInstance(
                    'In2code\\In2publishCore\\Domain\\Model\\Record',
                    'sys_file',
                    // create a temporary sys_file entry for the current
                    // identifier, since none was found nor could be reclaimed
                    // if persistTemporaryIndexing is enabled the entry is not temporary
                    // but this does not matter for the following code
                    array(),
                    $this->getFileInformation(
                        $foreignFileSystemFileIdentifier,
                        $foreignDriver,
                        $localDatabase,
                        $foreignDatabase
                    ),
                    $tcaService->getConfigurationArrayForTable('sys_file'),
                    array('foreignRecordExistsTemporary' => true)
                );
                $files[$temporarySysFile->getIdentifier()] = $temporarySysFile;
                unset($onlyForeignFileSystemFileIdentifiers[$index]);
            }
        }

        if (!empty($onlyForeignFileSystemFileIdentifiers)) {
            throw new \RuntimeException('Failed to convert all foreign files from disk to records', 1475236166);
        }

        // clean up again
        unset($localDatabase);
        unset($foreignDatabase);
        unset($localFolder);
        unset($localFileIdentifiers);
        unset($foreignFileIdentifiers);
        unset($onlyLocalFileSystemFileIdentifiers);
        unset($onlyForeignFileSystemFileIdentifiers);

        /*
         * Filtering:
         *  Notation:
         *      FFS = Foreign File System
         *      FDB = Foreign Database
         *      LFS = Local File System
         *      LDB = Local Database
         *  These filters files and entries we do not want to consider, because they do not represent an actual file.
         *  Prefer $localDriver over $foreignDriver where applicable, because it will be faster.
         */
        foreach ($files as $index => $file) {
            $fdb = $file->foreignRecordExists();
            $ldb = $file->localRecordExists();

            if ($file->hasLocalProperty('identifier')) {
                $localFileIdentifier = $file->getLocalProperty('identifier');
            } else {
                $localFileIdentifier = $file->getForeignProperty('identifier');
            }
            if ($file->hasForeignProperty('identifier')) {
                $foreignFileIdentifier = $file->getForeignProperty('identifier');
            } else {
                $foreignFileIdentifier = $file->getLocalProperty('identifier');
            }

            $lfs = $localDriver->fileExists($localFileIdentifier);
            $ffs = $foreignDriver->fileExists($foreignFileIdentifier);

            if ($ldb && !$lfs && !$ffs && !$fdb) {
                // CODE: [0] OLDB
                // The file exists only in the local database. Ignore the orphaned DB record.
                unset($files[$index]);
                continue;
            } elseif (!$ldb && $lfs && !$ffs && !$fdb) {
                // CODE: [1] OLFS
                // Create the local database entry by indexing the file
                // Assign the new information to the file and diff again
                // We end up in [4] OL

                // Since a (temporary) sys_file entry will be created for each file on disk
                // we will never end up in this case, but it's left here for documentary purposes
                throw new \LogicException(
                    'The FAL case OLFS is impossible due to prior record transformation',
                    1475178450
                );
            } elseif (!$ldb && !$lfs && $ffs && !$fdb) {
                // CODE: [2] OFFS
                // Try to index the file on foreign and reassign the foreign info.
                // Diff again and end up in [9] OF

                // Since a (temporary) sys_file entry will be created for each file on disk
                // we will never end up in this case, but it's left here for documentary purposes
                throw new \LogicException(
                    'The FAL case OFFS is impossible due to prior record transformation',
                    1475250513
                );
            } elseif (!$ldb && !$lfs && !$ffs && $fdb) {
                // CODE: [3] OFDB
                // The file exists only in the foreign database. Ignore the orphaned DB record.
                unset($files[$index]);
                continue;
            } elseif ($ldb && $lfs && !$ffs && !$fdb) {
                // CODE: [4] OL
                // Nothing to do here. The record exists only on local and will be displayed correctly.
                // The file and database record will be copied to the remote system when published.
            } elseif ($ldb && !$lfs && $ffs && !$fdb) {
                // CODE: [5] LDFF
                // Okay i currently don't know how to handle this, because on a record level this file
                // has been added, but on disk level the file was removed.
                // I think the best solution would be indexing the file on
                // foreign with the UID from the local database record.
                // That would lead us to [12] NLFS so at least it's one case less.

                // Since a (temporary) sys_file entry will be created for the foreign disk file and the
                // local database record will be ignored by overwriting it with an empty array we will
                // never end up in this case. It's still here for documentation
                throw new \LogicException(
                    'The FAL case LDFF is impossible due to prior record transformation',
                    1475252172
                );
            } elseif ($ldb && !$lfs && !$ffs && $fdb) {
                // CODE: [6] ODB
                // So there are two orphans (db without fs). we could diff them, but there's no file to publish.
                // I've decided to just ignore this case, since publishing  would not have an effect on the file system
                // and additionally i consider these files deleted, as this is a result of [12] NLFS
                unset($files[$index]);
                continue;
            } elseif (!$ldb && $lfs && $ffs && !$fdb) {
                // CODE: [7] OFS
                // We have the files on both sides.
                // Index them on both sides with the same UID for the sys_file and add that info to the record
                // Conveniently we end up in [14] ALL. Yai!

                // This case is handled by its PRE-FIX above. The if for this case must never be true.
                // This Exception is rather for documentation purposes than functional.
                throw new \LogicException(
                    'The FAL case OFS is impossible due to prior record transformation',
                    1475572486
                );
            } elseif (!$ldb && $lfs && !$ffs && $fdb) {
                // CODE: [8] LFFD
                // TODO
                // This might be one of the most strange setups.
                // Maybe the local file was deleted but write permissions blocked the deletion, but the database record
                // was deleted and not restored after failure. And the foreign database record? God knows...
                // Concrete: Index the local file and add that info to the record, diff again and go to [11] NFFS
            } elseif (!$ldb && !$lfs && $ffs && $fdb) {
                // CODE: [9] OF
                // Nothing to do here. The record exists only on local and will be displayed correctly.
                // The publish command removes the foreign file and database record
            } elseif ($ldb && $lfs && $ffs && !$fdb) {
                // CODE: [10] NFDB
                // TODO
                // Index the foreign file. Make sure the UID is the same as local's one.
                // Go to [14] ALL afterwards
            } elseif ($ldb && $lfs && !$ffs && $fdb) {
                // CODE: [11] NFFS
                // The foreign database record is orphaned.
                // The file was clearly deleted on foreign or the database record was prematurely published
                // Display this record as NEW (act like fdb would not exist, therefore like [4] OL
                // To achieve this we simply "unset" the foreign properties. Done.
                $file->setForeignProperties(array())->setDirtyProperties()->calculateState();
            } elseif ($ldb && !$lfs && $ffs && $fdb) {
                // CODE: [12] NLFS
                // TODO
                // The local database record is orphaned.
                // On foreign everything is okay.
                // Two cases: either the UID was assigned independent or the local file was removed
                // In both cases we will remove the remote file, because stage always wins.
                // No need to review this decision. LDB is orphaned, ignore it, act like it would be [9] OF
                // CARE: This will create the [6] ODB state.
            } elseif (!$ldb && $lfs && $ffs && $fdb) {
                // CODE: [13] NLDB
                // TODO
                // Create local database record by indexing the file.
                // Then add the created information to the record and diff again.
                // We will end up in [14]
            } elseif ($ldb && $lfs && $ffs && $fdb) {
                // CODE: [14] ALL
                // TODO DFS
            } elseif (!$ldb && !$lfs && !$ffs && !$fdb) {
                // CODE: [15] NONE
                // The file exists nowhere. Ignore it.
                unset($files[$index]);
                continue;
            } else {
                throw new \LogicException('This combination is not possible!', 1475065059);
            }
        }

        $record->addRelatedRecords($files);

        return $record;
    }

    /**
     * @param array $info
     * @return string
     */
    protected function createCombinedIdentifier(array $info)
    {
        $identifier = $info['identifier'];
        if (isset($info['folder'])) {
            $identifier = $info['folder'] . '/' . $identifier;
        }
        return sprintf('%d:%s', $info['storage'], $identifier);
    }

    /**
     * @param ResourceStorage $localStorage
     * @return DriverInterface
     */
    protected function getLocalDriver(ResourceStorage $localStorage)
    {
        $driverProperty = new PropertyReflection(get_class($localStorage), 'driver');
        $driverProperty->setAccessible(true);
        return $driverProperty->getValue($localStorage);
    }

    /**
     * @param ResourceStorage $localStorage
     * @return DriverInterface
     */
    protected function getForeignDriver(ResourceStorage $localStorage)
    {
        $foreignDriver = new RemoteFileAbstractionLayerDriver();
        $foreignDriver->setStorageUid($localStorage->getUid());
        $foreignDriver->initialize();
        return $foreignDriver;
    }

    /**
     * Factory method to create Record instances from a list of folder identifier
     *
     * @param array $subFolderIdentifiers
     * @param DriverInterface $localDriver
     * @param DriverInterface $foreignDriver
     * @return array
     */
    protected function getSubFolderRecordInstances(
        array $subFolderIdentifiers,
        DriverInterface $localDriver,
        DriverInterface $foreignDriver
    ) {
        $subFolders = array();
        foreach ($subFolderIdentifiers as $subFolderIdentifier) {
            if ($localDriver->folderExists($subFolderIdentifier)) {
                $localFolderInfo = $localDriver->getFolderInfoByIdentifier($subFolderIdentifier);
                $localFolderInfo['uid'] = $this->createCombinedIdentifier($localFolderInfo);
            } else {
                $localFolderInfo = array();
            }
            if ($foreignDriver->folderExists($subFolderIdentifier)) {
                $foreignFolderInfo = $foreignDriver->getFolderInfoByIdentifier($subFolderIdentifier);
                $foreignFolderInfo['uid'] = $this->createCombinedIdentifier($foreignFolderInfo);
            } else {
                $foreignFolderInfo = array();
            }

            $subFolders[] = GeneralUtility::makeInstance(
                'In2code\\In2publishCore\\Domain\\Model\\Record',
                'physical_folder',
                $localFolderInfo,
                $foreignFolderInfo,
                array(),
                array('depth' => 1)
            );
        }
        return $subFolders;
    }

    /**
     * @param Record[] $files
     * @return array of file identifiers in the current folder taken from the database
     * @throws \Exception
     */
    protected function buildIdentifiersList(array $files)
    {
        $identifierList = array();
        foreach ($files as $file) {
            $localIdentifier = null;
            if ($file->hasLocalProperty('identifier')) {
                $localIdentifier = $file->getLocalProperty('identifier');
            }

            $foreignIdentifier = null;
            if ($file->hasForeignProperty('identifier')) {
                $foreignIdentifier = $file->getForeignProperty('identifier');
            }

            if (null === $localIdentifier && null === $foreignIdentifier) {
                throw new \LogicException(
                    'A sys_file record must have at least a local or foreign identifier',
                    1475077830
                );
            }

            if (null !== $localIdentifier && $localIdentifier !== $foreignIdentifier && null !== $foreignIdentifier) {
                throw new \Exception('DEVELOPMENT EXCEPTION: Renamed? ' . $localIdentifier . ' ' . $foreignIdentifier);
            }

            $identifierList[$file->getIdentifier()] = null !== $localIdentifier ? $localIdentifier : $foreignIdentifier;
        }
        return $identifierList;
    }

    /**
     * This method is mostly a copy of an indexer method
     * @see \TYPO3\CMS\Core\Resource\Index\Indexer::gatherFileInformationArray
     *
     * @param string $identifier
     * @param DriverInterface $driver
     * @param DatabaseConnection $oppositeDatabase
     * @param DatabaseConnection $targetDatabase If null the sys_file record will not be persisted
     * @param int $uid Predefined UID
     * @return array
     */
    protected function getFileInformation(
        $identifier,
        DriverInterface $driver,
        DatabaseConnection $oppositeDatabase = null,
        DatabaseConnection $targetDatabase = null,
        $uid = 0
    ) {
        $fileInfo = $driver->getFileInfoByIdentifier($identifier);

        $mappingInfo = array(
            'mtime' => 'modification_date',
            'ctime' => 'creation_date',
            'mimetype' => 'mime_type',
        );

        unset($fileInfo['atime']);
        foreach ($mappingInfo as $fileInfoKey => $sysFileRecordKey) {
            $fileInfo[$sysFileRecordKey] = $fileInfo[$fileInfoKey];
            unset($fileInfo[$fileInfoKey]);
        }

        list($fileType) = explode('/', $fileInfo['mime_type']);
        switch (strtolower($fileType)) {
            case 'text':
                $type = File::FILETYPE_TEXT;
                break;
            case 'image':
                $type = File::FILETYPE_IMAGE;
                break;
            case 'audio':
                $type = File::FILETYPE_AUDIO;
                break;
            case 'video':
                $type = File::FILETYPE_VIDEO;
                break;
            case 'application':
            case 'software':
                $type = File::FILETYPE_APPLICATION;
                break;
            default:
                $type = File::FILETYPE_UNKNOWN;
        }

        $fileInfo['type'] = $type;
        $fileInfo['sha1'] = $driver->hash($identifier, 'sha1');
        $fileInfo['extension'] = PathUtility::pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $fileInfo['missing'] = 0;
        if ($uid > 0) {
            $fileInfo['uid'] = $uid;
        } else {
            $fileInfo['uid'] = $this->getReservedUid($targetDatabase, $oppositeDatabase);
        }

        if (true === $this->configuration['persistTemporaryIndexing']
            && null !== $oppositeDatabase
            && null !== $targetDatabase
        ) {
            $targetDatabase->exec_INSERTquery('sys_file', $this->prepareAndFilterSysFileDataForPersistence($fileInfo));
        }

        return $fileInfo;
    }

    /**
     * Increases the auto increment value on both databases until it is two higher than the highest taken uid.
     *
     * @param DatabaseConnection $leftDatabase
     * @param DatabaseConnection $rightDatabase
     * @return int
     */
    protected function getReservedUid(DatabaseConnection $leftDatabase, DatabaseConnection $rightDatabase)
    {
        // get the current auto increment of both databases
        $localAutoIncrement = $this->fetchSysFileAutoIncrementFromDatabase($leftDatabase);
        $foreignAutoIncrement = $this->fetchSysFileAutoIncrementFromDatabase($rightDatabase);

        // determine highest auto increment value from both databases
        $possibleUid = (int)max($localAutoIncrement, $foreignAutoIncrement);

        // initialize the variable holding the next higher auto increment value
        $nextAutoIncrement = $possibleUid;

        do {
            // increase the auto increment to "reserve" the previous integer
            $nextAutoIncrement++;
            $possibleUid = $nextAutoIncrement - 1;

            // apply the new auto increment on both databases
            $this->setAutoIncrement($leftDatabase, $nextAutoIncrement);
            $this->setAutoIncrement($rightDatabase, $nextAutoIncrement);
        } while (!$this->isUidFree($leftDatabase, $rightDatabase, $possibleUid));

        // return the free integer
        return $possibleUid;
    }

    /**
     * @param DatabaseConnection $leftDatabase
     * @param DatabaseConnection $rightDatabase
     * @param int $uid
     * @return bool
     */
    protected function isUidFree(DatabaseConnection $leftDatabase, DatabaseConnection $rightDatabase, $uid)
    {
        return 0 === $leftDatabase->exec_SELECTcountRows('uid', 'sys_file', 'uid=' . (int)$uid)
               && 0 === $rightDatabase->exec_SELECTcountRows('uid', 'sys_file', 'uid=' . (int)$uid);
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @param $autoIncrement
     */
    protected function setAutoIncrement(DatabaseConnection $databaseConnection, $autoIncrement)
    {
        $success = $databaseConnection->admin_query(
            'ALTER TABLE sys_file AUTO_INCREMENT = ' . (int)$autoIncrement
        );
        if (false === $success) {
            throw new \RuntimeException('Failed to increase auto_increment on sys_file', 1475248851);
        }
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @return int
     */
    protected function fetchSysFileAutoIncrementFromDatabase(DatabaseConnection $databaseConnection)
    {
        $queryResult = $databaseConnection->admin_query(
            'SHOW TABLE STATUS FROM '
            . $this->determineDatabaseOfConnection($databaseConnection)
            . ' WHERE name LIKE "sys_file";'
        );
        if (false === $queryResult) {
            throw new \RuntimeException('Could not select table status from database', 1475242494);
        }
        $resultData = $queryResult->fetch_assoc();
        if (!isset($resultData['Auto_increment'])) {
            throw new \RuntimeException('Could not fetch Auto_increment value from query result', 1475242706);
        }
        return (int)$resultData['Auto_increment'];
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @return string
     */
    protected function determineDatabaseOfConnection(DatabaseConnection $databaseConnection)
    {
        $queryResult = $databaseConnection->admin_query('SELECT DATABASE() as db_name;');
        if (false === $queryResult) {
            throw new \RuntimeException('Could not select database name from target database', 1475242213);
        }
        $resultData = $queryResult->fetch_assoc();
        if (!isset($resultData['db_name'])) {
            throw new \RuntimeException('Could not fetch database name from query result', 1475242337);
        }
        $queryResult->free();
        return $resultData['db_name'];
    }

    /**
     * @param array $data
     * @return array
     */
    protected function prepareAndFilterSysFileDataForPersistence(array $data)
    {
        $data = array_intersect_key(
            $data,
            array(
                'uid' => '',
                'pid' => '',
                'missing' => '',
                'type' => '',
                'storage' => '',
                'identifier' => '',
                'identifier_hash' => '',
                'extension' => '',
                'mime_type' => '',
                'name' => '',
                'sha1' => '',
                'size' => '',
                'creation_date' => '',
                'modification_date' => '',
                'folder_hash' => '',
            )
        );
        $data['tstamp'] = time();
        return $data;
    }
}
