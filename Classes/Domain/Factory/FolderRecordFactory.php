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
use In2code\In2publishCore\Domain\Repository\CommonRepository;
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

        unset($resourceFactory);

        // get the storages driver to prevent unintentional indexing
        $localDriver = $this->getLocalDriver($localStorage);
        $foreignDriver = $this->getForeignDriver($localStorage);

        unset($localStorage);

        // get the FAL-cleaned folder identifier
        $identifier = $localFolder->getIdentifier();
        $hashedIdentifier = $localFolder->getHashedIdentifier();

        $localFolderInfo = $this->getFolderInfoByIdentifierAndDriver($identifier, $localDriver);

        // retrieve all local sub folder identifiers (no recursion! no database!)
        // these are not Record instances, yet!
        $localSubFolders = $localDriver->getFoldersInFolder($identifier);

        unset($localFolder);

        // do the same on foreign, if the currently selected folder exists on foreign
        if ($foreignDriver->folderExists($identifier)) {
            $foreignFolderInfo = $this->getFolderInfoByIdentifierAndDriver($identifier, $foreignDriver);
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

        unset($localFolderInfo);
        unset($foreignFolderInfo);

        // create Record instances from the sub folder identifier lists
        $subFolders = $this->getSubFolderRecordInstances(
            array_merge($localSubFolders, $remoteSubFolders),
            $localDriver,
            $foreignDriver
        );

        unset($remoteSubFolders);
        unset($localSubFolders);

        // add all sub folder Records
        $record->addRelatedRecords($subFolders);

        unset($subFolders);

        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();

        // Now let's find all files in the selected folder
        // Get the Repo first
        $commonRepository = CommonRepository::getDefaultInstance('sys_file');

        // find all file database entries in the current folder by the folder's hash
        // (be sure to only use FAL methods for hashing)
        $files = $commonRepository->findByProperty('folder_hash', $hashedIdentifier);

        // get all occurring identifiers indexed by side in one array
        $indexedIdentifiers = $this->buildIndexedIdentifiersList($files);

        $diskIdentifiers = array(
            'local' => $this->getFilesIdentifiersInFolder($identifier, $localDriver),
            'foreign' => $this->getFilesIdentifiersInFolder($identifier, $foreignDriver),
        );

        $onlyFileSystemIdentifiers = $this->determineIdentifiersOnlyOnDisk($diskIdentifiers, $indexedIdentifiers);

        // TODO determine if this has to be done before or after the reclaimSysFileEntries feature
        // PRE-FIX for [7] OFS case.
        if (!empty($onlyFileSystemIdentifiers['both'])) {
            // iterate through all files found on the local and foreign disk but not in the database
            foreach ($onlyFileSystemIdentifiers['both'] as $index => $fileSystemEntryIdentifier) {
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
                unset($onlyFileSystemIdentifiers['both'][$index]);
            }
        }

        if (!empty($onlyFileSystemIdentifiers['both'])) {
            throw new \RuntimeException('Failed to convert all disk-only files to records', 1475253143);
        }

        // Determine file identifier of files which are found in one database and the opposite disk.
        // Files which exist on one side on disk and in the database are already filtered.
        // This builds the list for the LFFD and LDFF case
        $foreignFileRecordsToRecheck = array_intersect($indexedIdentifiers['local'], $diskIdentifiers['foreign']);
        $localFileRecordsToRecheck = array_intersect($indexedIdentifiers['foreign'], $diskIdentifiers['local']);

        unset($indexedIdentifiers);
        unset($diskIdentifiers);

        // determine identifiers on both local ond foreign disk and at least one database
        // This builds the list for the NFDB and NLDB case
        $fileIdentifiersOnBothSides = array_intersect($localFileRecordsToRecheck, $foreignFileRecordsToRecheck);

        // remove identifier entries from the arrays when the file was found on both sides.
        // this results in arrays that contain only file identifiers that occur on exactly one side.
        $foreignFileRecordsToRecheck = array_diff($foreignFileRecordsToRecheck, $fileIdentifiersOnBothSides);
        $localFileRecordsToRecheck = array_diff($localFileRecordsToRecheck, $fileIdentifiersOnBothSides);

        foreach ($foreignFileRecordsToRecheck as $fileRecordUid => $reCheckIdentifier) {
            $reCheckFile = $files[$fileRecordUid];
            $recordState = $reCheckFile->getState();
            if (RecordInterface::RECORD_STATE_ADDED === $recordState) {
                // PRE-FIX for [5] LDFF case, where the file was found on foreign's disk and the local database
                // Short: Removes LDB but adds FDB instead. Results in OF
                // The database record is technically added, but the file was removed. Since the file publishing is the
                // main domain of this class the state of the file on disk has precedence
                // add foreign file information instead
                $reCheckFile->setForeignProperties(
                    $this->getFileInformation(
                        $reCheckIdentifier,
                        $foreignDriver,
                        $localDatabase,
                        $foreignDatabase,
                        $reCheckFile->getLocalProperty('uid')
                    )
                );
                // remove all local properties to "ignore" the local database record
                $reCheckFile->setLocalProperties(array());
                $reCheckFile->addAdditionalProperty('foreignRecordExistsTemporary', true);
                // TODO: trigger the following inside the record itself so it can't be forgotten
                $reCheckFile->setDirtyProperties()->calculateState();
            } elseif (RecordInterface::RECORD_STATE_UNCHANGED === $recordState
                      || RecordInterface::RECORD_STATE_CHANGED === $recordState
            ) {
                // PRE-FIX [12] NLFS
                // The database record is unchanged or changed, because it exists on both sides,
                // the file in return was only found on foreign (the identifier is in $foreignFileRecordsToRecheck)
                $reCheckFile->setLocalProperties(array());
                $reCheckFile->setDirtyProperties()->calculateState();
            }
        }

        unset($foreignFileRecordsToRecheck);

        // PRE-FIX for [8] LFFD case, where the file was found on local's disc
        // and the foreign database (like [5] LDFF inverted)
        foreach ($localFileRecordsToRecheck as $fileRecordUid => $reCheckIdentifier) {
            $reCheckFile = $files[$fileRecordUid];
            // The database record is technically deleted, but the file was added. Since the file publishing is the
            // main domain of this class the state of the file on disk has precedence
            if (RecordInterface::RECORD_STATE_DELETED === $reCheckFile->getState()) {
                if (!$foreignDriver->fileExists($reCheckIdentifier)) {
                    // add local file information instead
                    $reCheckFile->setLocalProperties(
                        $this->getFileInformation(
                            $reCheckIdentifier,
                            $localDriver,
                            $foreignDatabase,
                            $localDatabase,
                            $reCheckFile->getForeignProperty('uid')
                        )
                    );
                    // remove all foreign properties to "ignore" the foreign database record
                    $reCheckFile->setForeignProperties(array());
                    $reCheckFile->addAdditionalProperty('localRecordExistsTemporary', true);
                    // TODO: trigger the following inside the record itself so it can't be forgotten
                    $reCheckFile->setDirtyProperties()->calculateState();
                }
            }
        }

        unset($localFileRecordsToRecheck);

        // Reconnect sys_file entries that definitely belong to the files found on disk but were not found because
        // the folder hash is broken
        if (true === $this->configuration['reclaimSysFileEntries']) {
            // the chance is vanishing low to find a file by its identifier in the database
            // because they should have been found by the folder hash already, but i'm a
            // generous developer and allow FAL to completely fuck up the folder hash
            foreach ($onlyFileSystemIdentifiers['local'] as $index => $localFileSystemFileIdentifier) {
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
                                    array('folder_hash' => $hashedIdentifier)
                                );
                                $localProperties = $sysFileEntry->getLocalProperties();
                                $localProperties['folder_hash'] = $hashedIdentifier;
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
                    unset($onlyFileSystemIdentifiers['local'][$index]);
                }
            }

            foreach ($onlyFileSystemIdentifiers['foreign'] as $index => $foreignFileSystemFileIdentifier) {
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
                                    array('folder_hash' => $hashedIdentifier)
                                );
                                $localProperties = $sysFileEntry->getForeignProperties();
                                $localProperties['folder_hash'] = $hashedIdentifier;
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
                    unset($onlyFileSystemIdentifiers['foreign'][$index]);
                }
            }
        }

        // PRE-FIX for the [1] OLFS case
        // create temporary sys_file entries for all files on the local disk
        if (!empty($onlyFileSystemIdentifiers['local'])) {
            // iterate through all files found on disk but not in the database
            foreach ($onlyFileSystemIdentifiers['local'] as $index => $localFileSystemFileIdentifier) {
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
                unset($onlyFileSystemIdentifiers['local'][$index]);
            }
        }

        if (!empty($onlyFileSystemIdentifiers['local'])) {
            throw new \RuntimeException('Failed to convert all local files from disk to records', 1475177184);
        }

        // PRE-FIX for the [2] OFFS case
        // create temporary sys_file entries for all files on the foreign disk
        if (!empty($onlyFileSystemIdentifiers['foreign'])) {
            // iterate through all files found on disc but not in the database
            foreach ($onlyFileSystemIdentifiers['foreign'] as $index => $foreignFileSystemFileIdentifier) {
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
                unset($onlyFileSystemIdentifiers['foreign'][$index]);
            }
        }

        if (!empty($onlyFileSystemIdentifiers['foreign'])) {
            throw new \RuntimeException('Failed to convert all foreign files from disk to records', 1475236166);
        }

        unset($onlyFileSystemIdentifiers);

        // PRE-FIXES
        foreach ($fileIdentifiersOnBothSides as $index => $fileIdentifierOnBothSides) {
            $reCheckFile = $files[$index];
            $recordState = $reCheckFile->getState();
            if (RecordInterface::RECORD_STATE_ADDED === $recordState) {
                // PRE-FIX for the [10] NFDB case
                // The file has been found on both file systems but only in the local database.
                // create a temporary counterpart for the local database entry, so we end up in [14] ALL
                $reCheckFile->setForeignProperties(
                    $this->getFileInformation(
                        $fileIdentifierOnBothSides,
                        $foreignDriver,
                        $localDatabase,
                        $foreignDatabase,
                        $reCheckFile->getIdentifier()
                    )
                );
                $reCheckFile->addAdditionalProperty('foreignRecordExistsTemporary', true);
                $reCheckFile->setDirtyProperties()->calculateState();
            } elseif (RecordInterface::RECORD_STATE_DELETED === $recordState) {
                // PRE-FIX for [13] NLDB
                // The file has been found on both file systems but not in the local database.
                // create a temporary local database entry with the uid of the existing foreign database entry.
                // Resulting case is [14] ALL
                $reCheckFile->setLocalProperties(
                    $this->getFileInformation(
                        $fileIdentifierOnBothSides,
                        $localDriver,
                        $foreignDatabase,
                        $localDatabase,
                        $reCheckFile->getIdentifier()
                    )
                );
                $reCheckFile->addAdditionalProperty('localRecordExistsTemporary', true);
                $reCheckFile->setDirtyProperties()->calculateState();
            }
        }

        // clean up again
        unset($fileIdentifiersOnBothSides);

        // mergeSysFileByIdentifier feature: find sys_file duplicates and "merge" them.
        // If the foreign sys_file was not referenced in the foreign's sys_file_reference table the the
        // uid of the foreign record can be overwritten to restore a consistent state
        if (true === $this->configuration['mergeSysFileByIdentifier']) {
            /** @var Record[][] $identifierList */
            $identifierList = array();
            // get all foreign file identifiers to match again
            foreach ($files as $file) {
                if (!$file->hasForeignProperty('identifier') || !$file->hasForeignProperty('uid')) {
                    continue;
                }

                if ($file->localRecordExists()
                    && (!$file->hasAdditionalProperty('localRecordExistsTemporary')
                        || true !== $file->getAdditionalProperty('localRecordExistsTemporary'))
                ) {
                    continue;
                }

                if (true === $file->getAdditionalProperty('foreignRecordExistsTemporary')) {
                    continue;
                }

                $identifierList[$file->getForeignProperty('identifier')][$file->getIdentifier()] = $file;
            }
            // find all matches
            foreach ($files as $file) {
                // condition: the sys_file exists on local, matches the identifier but is not the already added file
                // (UIDs are different, identifier is the same, record is not temporary)
                if ($file->hasLocalProperty('identifier')
                    && $file->hasLocalProperty('uid')
                    && isset($identifierList[$file->getLocalProperty('identifier')])
                    && !isset($identifierList[$file->getLocalProperty('identifier')][$file->getIdentifier()])
                    && (!$file->foreignRecordExists()
                        || true === $file->hasAdditionalProperty('foreignRecordExistsTemporary'))
                    && (!$file->hasAdditionalProperty('localRecordExistsTemporary')
                        || false === $file->hasAdditionalProperty('localRecordExistsTemporary'))
                ) {
                    $identifierList[$file->getLocalProperty('identifier')][$file->getIdentifier()] = $file;
                }
            }
            // filter the entries
            foreach ($identifierList as $identifierString => $fileEntries) {
                // only support sys_files with exactly one duplicate
                if (2 !== count($fileEntries)) {
                    unset($identifierList[$identifierString]);
                }
            }
            // if there are records "to be merged"
            if (!empty($identifierList)) {
                foreach ($identifierList as $identifierString => $fileEntries) {
                    // the first file is always the foreign file.
                    $foreignFile = array_shift($fileEntries);
                    $localFile = array_pop($fileEntries);
                    $oldUid = $foreignFile->getForeignProperty('uid');
                    $newUid = $localFile->getLocalProperty('uid');

                    // run the integrity test when enableSysFileReferenceUpdate is not enabled
                    if (true !== $this->configuration['enableSysFileReferenceUpdate']) {
                        // check if the sys_file was not referenced yet
                        $count = $foreignDatabase->exec_SELECTcountRows(
                            'uid',
                            'sys_file_reference',
                            'table_local LIKE "sys_file" AND uid_local=' . (int)$oldUid
                        );
                        // if a sys_file_record record has been found abort here,
                        // because it's unsafe to overwrite the uid
                        if (0 !== $count) {
                            break;
                        }
                    }
                    // check if the "new" uid is not taken yet
                    $count = $foreignDatabase->exec_SELECTcountRows(
                        'uid',
                        'sys_file',
                        'uid=' . (int)$newUid
                    );
                    // if a sys_file record with the "new" uid has been found abort immediately
                    if (0 !== $count) {
                        break;
                    }
                    $uidUpdateSuccess = $foreignDatabase->exec_UPDATEquery(
                        'sys_file',
                        'uid=' . (int)$oldUid,
                        array('uid' => $newUid)
                    );
                    if (true === $uidUpdateSuccess) {
                        $this->logger->notice(
                            'Rewrote a sys_file uid by the mergeSysFileByIdentifier feature',
                            array(
                                'old' => $oldUid,
                                'new' => $newUid,
                                'identifier' => $identifierString,
                            )
                        );

                        if (true === $this->configuration['enableSysFileReferenceUpdate']) {
                            $referenceUpdateSuccess = $foreignDatabase->exec_UPDATEquery(
                                'sys_file_reference',
                                'table_local LIKE "sys_file" AND uid_local=' . (int)$oldUid,
                                array('uid_local' => $newUid)
                            );
                            if ($referenceUpdateSuccess) {
                                $this->logger->notice(
                                    'Rewrote sys_file_reference by the enableSysFileReferenceUpdate feature',
                                    array(
                                        'old' => $oldUid,
                                        'new' => $newUid,
                                        'identifier' => $identifierString,
                                    )
                                );
                            } else {
                                $this->logger->error(
                                    'Failed to rewrite sys_file_reference by the enableSysFileReferenceUpdate feature',
                                    array(
                                        'old' => $oldUid,
                                        'new' => $newUid,
                                        'identifier' => $identifierString,
                                        'error' => $foreignDatabase->sql_error(),
                                        'errno' => $foreignDatabase->sql_errno(),
                                    )
                                );
                            }
                        }

                        // copy the foreign's properties with the new uid to the local record (merge)
                        $foreignProperties = $foreignFile->getForeignProperties();
                        $foreignProperties['uid'] = $newUid;
                        $localFile->setForeignProperties($foreignProperties);
                        $localFile->setDirtyProperties()->calculateState();

                        // remove the foreign file from the list
                        foreach ($files as $index => $file) {
                            if ($file === $foreignFile) {
                                unset($files[$index]);
                                break;
                            }
                        }
                    } else {
                        $this->logger->error(
                            'Failed to rewrite a sys_file uid by the mergeSysFileByIdentifier feature',
                            array(
                                'old' => $oldUid,
                                'new' => $newUid,
                                'identifier' => $identifierString,
                                'error' => $foreignDatabase->sql_error(),
                                'errno' => $foreignDatabase->sql_errno(),
                            )
                        );
                    }
                }
            }
        }
        $files = $this->filterFileRecords($files, $localDriver, $foreignDriver, $foreignDatabase, $localDatabase);

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
                array('depth' => 2)
            );
        }
        return $subFolders;
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
        $fileInfo['last_indexed'] = 0;
        $fileInfo['metadata'] = 0;
        $fileInfo['tstamp'] = time();
        $fileInfo['pid'] = 0;
        if ($uid > 0) {
            $fileInfo['uid'] = $uid;
        } else {
            $fileInfo['uid'] = $this->getReservedUid($targetDatabase, $oppositeDatabase);
        }

        // convert all values to string to match the resulting types of a database select query result
        foreach ($fileInfo as $index => $value) {
            $fileInfo[$index] = (string)$value;
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

    /**
     * Filtering:
     *  Notation:
     *      FFS = Foreign File System
     *      FDB = Foreign Database
     *      LFS = Local File System
     *      LDB = Local Database
     *  These filters files and entries we do not want to consider, because they do not represent an actual file.
     *  Prefer $localDriver over $foreignDriver where applicable, because it will be faster.
     *
     * @param Record[] $files
     * @param DriverInterface $localDriver
     * @param DriverInterface $foreignDriver
     * @param DatabaseConnection $foreignDatabase
     * @param DatabaseConnection $localDatabase
     * @return Record[]
     */
    protected function filterFileRecords(
        array $files,
        DriverInterface $localDriver,
        DriverInterface $foreignDriver,
        DatabaseConnection $foreignDatabase,
        DatabaseConnection $localDatabase
    ) {
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

                // Edit: [12] NLFS has also received a PRE-FIX. The resulting case is [9] OF

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
                // This might be one of the most strange setups.
                // Maybe the local file was deleted but write permissions blocked the deletion, but the database record
                // was deleted and not restored after failure. And the foreign database record? God knows...
                // Concrete: Index the local file and add that info to the record, diff again and go to [11] NFFS

                // Hint: This is done by the PRE-FIX for [8] LFFD.
                // This Exception is rather for documentation purposes than functional.
                throw new \LogicException(
                    'The FAL case LFFD is impossible due to prior record transformation',
                    1475573724
                );
            } elseif (!$ldb && !$lfs && $ffs && $fdb) {
                // CODE: [9] OF
                // Nothing to do here. The record exists only on local and will be displayed correctly.
                // The publish command removes the foreign file and database record
            } elseif ($ldb && $lfs && $ffs && !$fdb) {
                // CODE: [10] NFDB
                // Index the foreign file. Make sure the UID is the same as local's one.
                // Go to [14] ALL afterwards

                // Hint: This is done by the PRE-FIX for [10] NFDB.
                // This Exception is rather for documentation purposes than functional.
                throw new \LogicException(
                    'The FAL case NFDB is impossible due to prior record transformation',
                    1475576764
                );
            } elseif ($ldb && $lfs && !$ffs && $fdb) {
                // CODE: [11] NFFS
                // The foreign database record is orphaned.
                // The file was clearly deleted on foreign or the database record was prematurely published
                // Display this record as NEW (act like fdb would not exist, therefore like [4] OL
                // To achieve this we simply "unset" the foreign properties. Done.
                $file->setForeignProperties(array())->setDirtyProperties()->calculateState();
            } elseif ($ldb && !$lfs && $ffs && $fdb) {
                // CODE: [12] NLFS
                // The local database record is orphaned.
                // On foreign everything is okay.
                // Two cases: either the UID was assigned independent or the local file was removed
                // In both cases we will remove the remote file, because stage always wins.
                // No need to review this decision. LDB is orphaned, ignore it, act like it would be [9] OF
                // CARE: This will create the [6] ODB state.

                // Hint: This is done by a PRE-FIX.
                // This Exception is rather for documentation purposes than functional.
                throw new \LogicException(
                    'The FAL case NLFS is impossible due to prior record transformation',
                    1475576764
                );
            } elseif (!$ldb && $lfs && $ffs && $fdb) {
                // CODE: [13] NLDB
                // Create local database record by indexing the file.
                // Then add the created information to the record and diff again.
                // We will end up in [14]

                // Hint: This is done by a PRE-FIX.
                // This Exception is rather for documentation purposes than functional.
                throw new \LogicException(
                    'The FAL case NLDB is impossible due to prior record transformation',
                    1475578482
                );
            } elseif ($ldb && $lfs && $ffs && $fdb) {
                // CODE: [14] ALL
                if (RecordInterface::RECORD_STATE_UNCHANGED === $file->getState()) {
                    // the database records are identical, but this does not necessarily reflect the truth,
                    // because files might have changed in the file system without FAL noticing these changes.
                    $file->setLocalProperties(
                        $this->getFileInformation(
                            $localFileIdentifier,
                            $localDriver,
                            $foreignDatabase,
                            $localDatabase,
                            $file->getIdentifier()
                        )
                    );
                    $file->setForeignProperties(
                        $this->getFileInformation(
                            $foreignFileIdentifier,
                            $foreignDriver,
                            $localDatabase,
                            $foreignDatabase,
                            $file->getIdentifier()
                        )
                    );
                    $file->setDirtyProperties()->calculateState();
                }
            } elseif (!$ldb && !$lfs && !$ffs && !$fdb) {
                // CODE: [15] NONE
                // The file exists nowhere. Ignore it.
                unset($files[$index]);
                continue;
            } else {
                throw new \LogicException('This combination is not possible!', 1475065059);
            }
            $file->addAdditionalProperty('depth', 2);
            $file->addAdditionalProperty('isAuthoritative', true);
        }
        return $files;
    }

    /**
     * @param string $identifier
     * @param DriverInterface $localDriver
     * @return array
     */
    protected function getFolderInfoByIdentifierAndDriver($identifier, DriverInterface $localDriver)
    {
        // fetch all information regarding the folder
        $localFolderInfo = $localDriver->getFolderInfoByIdentifier($identifier);
        // add the "uid" property, which is largely exclusive set for Record::isRecordRepresentByProperties
        // but additionally a good place to store the "combined identifier"
        $localFolderInfo['uid'] = $this->createCombinedIdentifier($localFolderInfo);
        return $localFolderInfo;
    }

    /**
     * Builds a list of all file identifiers on local and foreign that are indexed in the database,
     * so files only existing on disk can be determined by diff-ing against this list
     *
     * @param Record[] $files
     * @return array
     */
    protected function buildIndexedIdentifiersList(array $files)
    {
        $indexedIdentifiers = array(
            'local' => array(),
            'foreign' => array(),
        );

        foreach ($files as $file) {
            if ($file->hasLocalProperty('identifier')) {
                $indexedIdentifiers['local'][$file->getIdentifier()] = $file->getLocalProperty('identifier');
            }
            if ($file->hasForeignProperty('identifier')) {
                $indexedIdentifiers['foreign'][$file->getIdentifier()] = $file->getForeignProperty('identifier');
            }
        }
        return $indexedIdentifiers;
    }

    /**
     * @param string $identifier
     * @param DriverInterface $driver
     * @return array
     */
    protected function getFilesIdentifiersInFolder($identifier, DriverInterface $driver)
    {
        if ($driver->folderExists($identifier)) {
            $identifierList = array_values($driver->getFilesInFolder($identifier));
        } else {
            $identifierList = array();
        }
        return $identifierList;
    }

    /**
     * @param array $diskIdentifiers
     * @param array $indexedIdentifiers
     * @return array
     */
    protected function determineIdentifiersOnlyOnDisk(array $diskIdentifiers, array $indexedIdentifiers)
    {
        $onlyDiskIdentifiers = array();

        // find all files which are not indexed (don't care of files in DB but not in FS)
        // diff against both local and foreign indexed files. This will identify all files
        // that are not represented by a sys_file on any side.
        // local files on disk indexed on foreign are handled by LFFD
        $onlyDiskIdentifiers['local'] = array_diff(
            $diskIdentifiers['local'],
            $indexedIdentifiers['local'],
            $indexedIdentifiers['foreign']
        );

        // get all disk identifiers not occurring in any of the database identifiers
        $onlyDiskIdentifiers['foreign'] = array_diff(
            $diskIdentifiers['foreign'],
            $indexedIdentifiers['local'],
            $indexedIdentifiers['foreign']
        );

        // Move files existing on both disks but not in any database to a third array.
        $onlyDiskIdentifiers['both'] = array_intersect(
            $onlyDiskIdentifiers['local'],
            $onlyDiskIdentifiers['foreign']
        );
        $onlyDiskIdentifiers['local'] = array_diff(
            $onlyDiskIdentifiers['local'],
            $onlyDiskIdentifiers['both']
        );
        $onlyDiskIdentifiers['foreign'] = array_diff(
            $onlyDiskIdentifiers['foreign'],
            $onlyDiskIdentifiers['both']
        );
        return $onlyDiskIdentifiers;
    }
}
