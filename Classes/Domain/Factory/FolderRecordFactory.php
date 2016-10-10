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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Service\Database\UidReservationService;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
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
     * @var CommonRepository
     */
    protected $commonRepository;

    /**
     * @var DatabaseConnection
     */
    protected $localDatabase;

    /**
     * @var DatabaseConnection
     */
    protected $foreignDatabase;

    /**
     * @var array
     */
    protected $configuration = array();

    /**
     * @var DriverInterface
     */
    protected $localDriver;

    /**
     * @var DriverInterface
     */
    protected $foreignDriver;

    /**
     * @var FileIndexFactory
     */
    protected $fileIndexFactory = null;

    /**
     * @var UidReservationService
     */
    protected $uidReservationService = null;

    /**
     * FolderRecordFactory constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
        $this->commonRepository = CommonRepository::getDefaultInstance('sys_file');
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        $this->configuration = ConfigurationUtility::getConfiguration('factory.fal');
        $this->uidReservationService = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Service\\Database\\UidReservationService'
        );
    }

    /**
     * @param string $identifier
     * @return Folder
     */
    protected function initializeDependenciesAndGetFolder($identifier)
    {
        // Grab the resource factory to get the FAL driver of the selected folder "FAL style"
        $resourceFactory = ResourceFactory::getInstance();

        // Determine the current folder. If the identifier is NULL there was no folder selected.
        if (null === $identifier) {
            // Special case: The module was opened, but no storage/folder has been selected.
            // Get the default storage and the default folder to show.
            $localStorage = $resourceFactory->getDefaultStorage();
            // Notice: ->getDefaultFolder does not return the default folder to show, but to upload files to.
            // The root level folder is the "real" default and also respects mount points of the current user.
            $localFolder = $localStorage->getRootLevelFolder();
        } else {
            // This is the normal case. The identifier identifies the folder inclusive its storage.
            $localFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier($identifier);
            $localStorage = $localFolder->getStorage();
        }

        // Get the storages driver to prevent unintentional indexing by using storage methods.
        $this->localDriver = $this->getLocalDriver($localStorage);
        $this->foreignDriver = $this->getForeignDriver($localStorage);

        $this->fileIndexFactory = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Factory\\FileIndexFactory',
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
        $localFolder = $this->initializeDependenciesAndGetFolder($identifier);

        // Get the FAL-cleaned folder identifier (this should not be necessary, but i mistrust FAL)
        $identifier = $localFolder->getIdentifier();
        // Also get the hashed identifier, which will be used later for temporary index creation and record searching.
        $hashedIdentifier = $localFolder->getHashedIdentifier();

        // Remove the reference to the local folder FAL object.
        // It's useless now, because i've got its identifiers and sub folders.
        unset($localFolder);

        $record = $this->createRecordForSelectedFolderAndRelatedSubFolders($identifier);

        // Now let's find all files inside of the selected folder by the folders hash.
        $files = $this->commonRepository->findByProperty('folder_hash', $hashedIdentifier);

        // Build a list of all file identifiers found in both databases.
        // The resulting array has the keys local, foreign and both to indicate where the identifier was indexed.
        $indexedIdentifiers = $this->buildIndexedIdentifiersList($files);

        // Get all occurring identifiers of files in the current folder.
        // This array has the three keys local, foreign and both, too. Therefore we know where the files were found.
        $diskIdentifiers = $this->buildDiskIdentifiersList($identifier);

        // Remove all identifiers found in the databases from the disk identifiers list.
        // These identifiers do only occur on the local and/or foreign disk.
        // This list is important for any OxFS case. (local = OLFS; foreign = OFFS, both = OFS)
        $onlyDiskIdentifiers = $this->determineIdentifiersOnlyOnDisk($diskIdentifiers, $indexedIdentifiers);

        // Create temporary indices for files existing on local and foreign but in neither database (OFS)
        $files = $this->convertAndAddUnIndexedFilesOnBothDisksToRecordList($onlyDiskIdentifiers, $files);

        $files = $this->fixAndConvertIntersectingIdentifiers($diskIdentifiers, $indexedIdentifiers, $files);

        // Reconnect sys_file entries that definitely belong to the files found on disk but were not found because
        // the folder hash is broken
        if (true === $this->configuration['reclaimSysFileEntries']) {
            list($files, $onlyDiskIdentifiers) = $this->reclaimIndexEntries(
                $onlyDiskIdentifiers,
                $hashedIdentifier,
                $files
            );
        }

        $files = $this->convertAndAddOnlyDiskIdentifiersToFileRecords($onlyDiskIdentifiers, $files);

        // remove OxFS identifiers, they have all been converted to records.
        unset($onlyDiskIdentifiers);

        $files = $this->indexFilesWithMissingIndexOnOneSide($indexedIdentifiers, $diskIdentifiers, $files);

        // mergeSysFileByIdentifier feature: find sys_file duplicates and "merge" them.
        // If the foreign sys_file was not referenced in the foreign's sys_file_reference table the the
        // uid of the foreign record can be overwritten to restore a consistent state
        if (true === $this->configuration['mergeSysFileByIdentifier']) {
            $files = $this->mergeSysFileByIdentifier($files);
        }

        $files = $this->filterFileRecords($files);

        return $record->addRelatedRecords($files);
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
        $this->foreignDriver = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Driver\\RemoteFileAbstractionLayerDriver'
        );
        $this->foreignDriver->setStorageUid($localStorage->getUid());
        $this->foreignDriver->initialize();
        return $this->foreignDriver;
    }

    /**
     * Factory method to create Record instances from a list of folder identifier
     *
     * @param array $localSubFolders
     * @param array $foreignSubFolders
     * @return array
     */
    protected function getSubFolderRecordInstances(array $localSubFolders, array $foreignSubFolders)
    {
        $subFolderIdentifiers = array_merge($localSubFolders, $foreignSubFolders);
        $subFolders = array();
        foreach ($subFolderIdentifiers as $subFolderIdentifier) {
            $subFolders[] = GeneralUtility::makeInstance(
                'In2code\\In2publishCore\\Domain\\Model\\Record',
                'physical_folder',
                $this->getFolderInfoByDriverAndIdentifier($this->localDriver, $subFolderIdentifier),
                $this->getFolderInfoByDriverAndIdentifier($this->foreignDriver, $subFolderIdentifier),
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

        $mappingInfo = array('mtime' => 'modification_date', 'ctime' => 'creation_date', 'mimetype' => 'mime_type');

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
            $fileInfo['uid'] = $this->uidReservationService->getReservedUid();
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
     *  These filters files and entries i do not consider, because they do not represent an actual file.
     *  Prefer $this->localDriver over $foreignDriver where applicable, because it will be faster.
     *
     * @param Record[] $files
     * @return Record[]
     */
    protected function filterFileRecords(array $files)
    {
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

            $lfs = $this->localDriver->fileExists($localFileIdentifier);
            $ffs = $this->foreignDriver->fileExists($foreignFileIdentifier);

            if ($ldb && !$lfs && !$ffs && !$fdb) {
                // CODE: [0] OLDB; The file exists only in the local database. Ignore the orphaned DB record.
                unset($files[$index]);
                continue;
            } elseif (!$ldb && $lfs && !$ffs && !$fdb) {
                // CODE: [1] OLFS; Fixed earlier. See [4] OL
                throw new \LogicException(
                    'The FAL case OLFS is impossible due to prior record transformation',
                    1475178450
                );
            } elseif (!$ldb && !$lfs && $ffs && !$fdb) {
                // CODE: [2] OFFS; Fixed earlier. See [9] OF
                throw new \LogicException(
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
                throw new \LogicException(
                    'The FAL case LDFF is impossible due to prior record transformation',
                    1475252172
                );
            } elseif ($ldb && !$lfs && !$ffs && $fdb) {
                // CODE: [6] ODB; Both indices are orphaned. Ignore them. This might be a result of [12] NLFS
                unset($files[$index]);
                continue;
            } elseif (!$ldb && $lfs && $ffs && !$fdb) {
                // CODE: [7] OFS; Both disk files were indexed. See [14] ALL
                throw new \LogicException(
                    'The FAL case OFS is impossible due to prior record transformation',
                    1475572486
                );
            } elseif (!$ldb && $lfs && !$ffs && $fdb) {
                // CODE: [8] LFFD. Ignored foreign database record, indexed local disk file. See [11] NFFS
                throw new \LogicException(
                    'The FAL case LFFD is impossible due to prior record transformation',
                    1475573724
                );
            } elseif (!$ldb && !$lfs && $ffs && $fdb) {
                // CODE: [9] OF; Nothing to do here;
            } elseif ($ldb && $lfs && $ffs && !$fdb) {
                // CODE: [10] NFDB; Indexed the foreign file. See [14] ALL
                throw new \LogicException(
                    'The FAL case NFDB is impossible due to prior record transformation',
                    1475576764
                );
            } elseif ($ldb && $lfs && !$ffs && $fdb) {
                // CODE: [11] NFFS; The foreign database record is orphaned and will be ignored.
                $file->setForeignProperties(array())->setDirtyProperties()->calculateState();
            } elseif ($ldb && !$lfs && $ffs && $fdb) {
                // CODE: [12] NLFS; The local database record is orphaned and will be ignored.
                $file->setLocalProperties(array())->setDirtyProperties()->calculateState();
            } elseif (!$ldb && $lfs && $ffs && $fdb) {
                // CODE: [13] NLDB; Indexed the local disk file. See [14] ALL
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
                            $this->localDriver,
                            $this->foreignDatabase,
                            $this->localDatabase,
                            $file->getIdentifier()
                        )
                    );
                    $file->setForeignProperties(
                        $this->getFileInformation(
                            $foreignFileIdentifier,
                            $this->foreignDriver,
                            $this->localDatabase,
                            $this->foreignDatabase,
                            $file->getIdentifier()
                        )
                    );
                    $file->setDirtyProperties()->calculateState();
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
     * @param string $identifier
     * @return array
     */
    protected function getFolderInfoByIdentifierAndDriver($identifier)
    {
        // fetch all information regarding the folder
        $localFolderInfo = $this->localDriver->getFolderInfoByIdentifier($identifier);
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
            'both' => array(),
        );

        foreach ($files as $file) {
            $identifier = $file->getIdentifier();

            $isLocal = $file->hasLocalProperty('identifier');
            $isForeign = $file->hasForeignProperty('identifier');

            // hint: not existing properties will just return null
            $localIdentifier = $file->getLocalProperty('identifier');
            $foreignIdentifier = $file->getForeignProperty('identifier');

            // if the record was indexed on both sides
            if ($isLocal && $isForeign) {
                if ($localIdentifier === $foreignIdentifier) {
                    // if the identifiers are the same: mark the as "indexed on both sides"
                    $indexedIdentifiers['both'][$identifier] = $localIdentifier;
                } else {
                    // otherwise mark it as indexed on the respective side. this takes care of moved (renamed) files
                    $indexedIdentifiers['local'][$identifier] = $localIdentifier;
                    $indexedIdentifiers['foreign'][$identifier] = $foreignIdentifier;
                }
            } elseif ($isLocal && !$isForeign) {
                // only local
                $indexedIdentifiers['local'][$identifier] = $localIdentifier;
            } elseif (!$isLocal && $isForeign) {
                // only foreign
                $indexedIdentifiers['foreign'][$identifier] = $foreignIdentifier;
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
        $diskIdentifiers['local'] = array_diff(
            $diskIdentifiers['local'],
            $indexedIdentifiers['local'],
            $indexedIdentifiers['foreign'],
            $indexedIdentifiers['both']
        );

        $diskIdentifiers['foreign'] = array_diff(
            $diskIdentifiers['foreign'],
            $indexedIdentifiers['local'],
            $indexedIdentifiers['foreign'],
            $indexedIdentifiers['both']
        );

        $diskIdentifiers['both'] = array_diff(
            $diskIdentifiers['both'],
            $indexedIdentifiers['local'],
            $indexedIdentifiers['foreign'],
            $indexedIdentifiers['both']
        );

        return $diskIdentifiers;
    }

    /**
     * PRE-FIX for [7] OFS case.
     *
     * @param array $onlyDiskIdentifiers
     * @param Record[] $files
     * @return Record[]
     */
    protected function convertAndAddUnIndexedFilesOnBothDisksToRecordList(array $onlyDiskIdentifiers, array $files)
    {
        if (!empty($onlyDiskIdentifiers['both'])) {
            // Iterate through all files found on the local and foreign disk but not in the database.
            foreach ($onlyDiskIdentifiers['both'] as $onlyDiskIdentifier) {
                // Fetch the file information with a (on the fly) reserved uid.
                $localFileInformation = $this->getFileInformation(
                    $onlyDiskIdentifier,
                    $this->localDriver,
                    $this->foreignDatabase,
                    $this->localDatabase
                );
                $foreignFileInfo = $this->getFileInformation(
                    $onlyDiskIdentifier,
                    $this->foreignDriver,
                    $this->localDatabase,
                    $this->foreignDatabase,
                    $localFileInformation['uid']
                );
                $temporarySysFile = $this->fileIndexFactory->makeInstance($localFileInformation, $foreignFileInfo);
                $files[$temporarySysFile->getIdentifier()] = $temporarySysFile;
            }
        }

        return $files;
    }

    /**
     * @param array $diskIdentifiers
     * @param array $files
     * @param string $side
     * @return array
     */
    protected function convertAndAddOnlyDiskIdentifiersToFileRecordsBySide(array $diskIdentifiers, array $files, $side)
    {
        if ($side === 'local') {
            $driver = $this->localDriver;
            $targetDatabase = $this->localDatabase;
            $oppositeDatabase = $this->foreignDatabase;
        } elseif ($side === 'foreign') {
            $driver = $this->foreignDriver;
            $targetDatabase = $this->foreignDatabase;
            $oppositeDatabase = $this->localDatabase;
        } else {
            throw new \LogicException('Unsupported side "' . $side . '"', 1476101719);
        }
        if (!empty($diskIdentifiers[$side])) {
            // iterate through all files found on disc but not in the database
            foreach ($diskIdentifiers[$side] as $onlyDiskIdentifier) {
                // create a temporary sys_file entry for the current
                // identifier, since none was found nor could be reclaimed
                // if persistTemporaryIndexing is enabled the entry is not temporary
                // but this does not matter for the following code
                $properties = $this->getFileInformation(
                    $onlyDiskIdentifier,
                    $driver,
                    $oppositeDatabase,
                    $targetDatabase
                );
                $temporarySysFile = $this->fileIndexFactory->makeInstanceForSide($side, $properties);
                $temporarySysFile->setDirtyProperties()->calculateState();
                $files[$temporarySysFile->getIdentifier()] = $temporarySysFile;
            }
        }

        return $files;
    }

    /**
     * @param Record $record
     * @param string $identifier
     * @param string $side
     */
    protected function createAndAddTemporaryIndexInformationToRecordForSide(Record $record, $identifier, $side)
    {
        if ($side === 'local') {
            $driver = $this->localDriver;
            $targetDatabase = $this->localDatabase;
            $oppositeDatabase = $this->foreignDatabase;
        } elseif ($side === 'foreign') {
            $driver = $this->foreignDriver;
            $targetDatabase = $this->foreignDatabase;
            $oppositeDatabase = $this->localDatabase;
        } else {
            throw new \LogicException('Unsupported side "' . $side . '"', 1476101470);
        }
        $record->setPropertiesBySideIdentifier(
            $side,
            $this->getFileInformation(
                $identifier,
                $driver,
                $oppositeDatabase,
                $targetDatabase,
                $record->getPropertyBySideIdentifier($side === 'local' ? 'foreign' : 'local', 'uid')
            )
        );
        $record->addAdditionalProperty($side . 'RecordExistsTemporary', true);
        $record->setDirtyProperties()->calculateState();
    }

    /**
     * Search on the disk for all files in the current folder and build a list of file identifiers
     * for each local and foreign, so i can identify e.g. not indexed files.
     * Move all entries occurring on both sides to the "both" index afterwards.
     *
     * @param string $identifier
     * @return array
     */
    protected function buildDiskIdentifiersList($identifier)
    {
        $diskIdentifiers = array(
            'local' => $this->getFilesIdentifiersInFolder($identifier, $this->localDriver),
            'foreign' => $this->getFilesIdentifiersInFolder($identifier, $this->foreignDriver),
            'both' => array(),
        );

        $diskIdentifiers['both'] = array_intersect($diskIdentifiers['local'], $diskIdentifiers['foreign']);
        $diskIdentifiers['local'] = array_diff($diskIdentifiers['local'], $diskIdentifiers['both']);
        $diskIdentifiers['foreign'] = array_diff($diskIdentifiers['foreign'], $diskIdentifiers['both']);

        return $diskIdentifiers;
    }

    /**
     * @param string $identifier
     * @return Record
     */
    protected function createRecordForSelectedFolderAndRelatedSubFolders($identifier)
    {
        $foreignFolderExists = $this->foreignDriver->folderExists($identifier);

        $record = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Model\\Record',
            'physical_folder',
            $this->getFolderInfoByIdentifierAndDriver($identifier),
            $foreignFolderExists ? $this->getFolderInfoByIdentifierAndDriver($identifier) : array(),
            array(),
            array('depth' => 1)
        );

        // Add all converted sub folder records to the selected folder.
        $record->addRelatedRecords(
            $this->getSubFolderRecordInstances(
                $this->localDriver->getFoldersInFolder($identifier),
                $foreignFolderExists ? $this->foreignDriver->getFoldersInFolder($identifier) : array()
            )
        );

        return $record;
    }

    /**
     * @param array $diskIdentifiers
     * @param array $indexedIdentifiers
     * @param Record[] $files
     * @param string $diskSide
     * @return Record[]
     */
    protected function fixAndConvertIntersectingIdentifiersIntoRecords(
        array $diskIdentifiers,
        array $indexedIdentifiers,
        array $files,
        $diskSide
    ) {
        // Find intersecting identifiers. These are identifiers of files on the local disk and foreign database
        // or vice versa.
        // The resulting lists are important for the
        // LFFD (local file foreign database) and LDFF (local database foreign file) case.
        $indexSide = $diskSide === 'foreign' ? 'local' : 'foreign';
        $diskNotIndexedHere = array_diff(
            $diskIdentifiers[$diskSide],
            $indexedIdentifiers[$diskSide],
            $indexedIdentifiers['both']
        );
        $intersectingIdentifiers = array_intersect($indexedIdentifiers[$indexSide], $diskNotIndexedHere);

        foreach ($intersectingIdentifiers as $fileRecordUid => $identifier) {
            $file = $files[$fileRecordUid];
            $state = $file->getState();
            if ($diskSide === 'foreign' && RecordInterface::RECORD_STATE_ADDED === $state) {
                // PRE-FIX for [5] LDFF case, where the file was found on foreign's disk and the local database
                // and the foreign database (like [8] LFFD inverted)
                // Short: Removes LDB but adds FDB instead. Results in OF
                // The database record is technically added, but the file was removed. Since the file publishing is the
                // main domain of this class the state of the file on disk has precedence
                // add foreign file information instead
                $this->createAndAddTemporaryIndexInformationToRecordForSide($file, $identifier, 'foreign');

                // remove all local properties to "ignore" the local database record
                $file->setLocalProperties(array());
                $file->setDirtyProperties()->calculateState();
            } elseif ($diskSide === 'local' && RecordInterface::RECORD_STATE_DELETED === $state) {
                if (!$this->foreignDriver->fileExists($identifier)) {
                    // PRE-FIX for [8] LFFD case, where the file was found on local's disc
                    // and the foreign database (like [5] LDFF inverted).
                    // The database record is technically deleted, but the file was added. Since the file
                    // publishing is the main domain of this class the state of the file on disk has precedence
                    $this->createAndAddTemporaryIndexInformationToRecordForSide($file, $identifier, 'local');
                    // remove all foreign properties to "ignore" the foreign database record
                    $file->setForeignProperties(array());
                    $file->setDirtyProperties()->calculateState();
                }
            }
        }
        return $files;
    }

    /**
     * @param array $onlyDiskIdentifiers
     * @param string $hashedIdentifier
     * @param Record[] $files
     * @return Record[]
     */
    protected function reclaimIndexEntries(array $onlyDiskIdentifiers, $hashedIdentifier, array $files)
    {
        list($onlyDiskIdentifiers, $files) = $this->reclaimSysFileEntriesBySide(
            $onlyDiskIdentifiers,
            $this->localDatabase,
            $hashedIdentifier,
            $files,
            'local'
        );
        list($onlyDiskIdentifiers, $files) = $this->reclaimSysFileEntriesBySide(
            $onlyDiskIdentifiers,
            $this->foreignDatabase,
            $hashedIdentifier,
            $files,
            'foreign'
        );

        return array($files, $onlyDiskIdentifiers);
    }

    /**
     * @param array $onlyDiskIdentifiers
     * @param DatabaseConnection $targetDatabase
     * @param $hashedIdentifier
     * @param Record[] $files
     * @param string $side
     * @return array
     */
    protected function reclaimSysFileEntriesBySide(
        array $onlyDiskIdentifiers,
        DatabaseConnection $targetDatabase,
        $hashedIdentifier,
        array $files,
        $side
    ) {
        // the chance is vanishing low to find a file by its identifier in the database
        // because they should have been found by the folder hash already, but i'm a
        // generous developer and allow FAL to completely fuck up the folder hash
        foreach ($onlyDiskIdentifiers[$side] as $index => $onlyDiskIdentifier) {
            $disconnectedSysFiles = $this->commonRepository->findByProperty('identifier', $onlyDiskIdentifier);
            // if a sys_file record could be reclaimed use it
            if (!empty($disconnectedSysFiles)) {
                // repair the entry a.k.a reconnect it by updating the folder hash
                if (true === $this->configuration['autoRepairFolderHash']) {
                    foreach ($disconnectedSysFiles as $sysFileEntry) {
                        // update on the local side if record has been found on the local side.
                        // Hint: Do *not* update foreign. The folder hash on foreign might be correctly different
                        // e.g. in case the file was moved
                        $property = $sysFileEntry->getPropertyBySideIdentifier($side, 'folder_hash');
                        if (null !== $property) {
                            $targetDatabase->exec_UPDATEquery(
                                'sys_file',
                                'uid=' . $sysFileEntry->getIdentifier(),
                                array('folder_hash' => $hashedIdentifier)
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
        return array($onlyDiskIdentifiers, $files);
    }

    /**
     * condition: the sys_file exists on local, matches the identifier but is not the already added file
     * (UIDs are different, identifier is the same, record is not temporary)
     *
     * @param array $identifierList
     * @param Record $file
     * @return bool
     */
    protected function isLocalIndexWithMatchingDuplicateIndexOnForeign(array $identifierList, Record $file)
    {
        return null !== ($localIdentifier = $file->getLocalProperty('identifier'))
               && null !== ($localUid = $file->getLocalProperty('uid'))
               // There is a foreign record with the same identifier
               && isset($identifierList[$localIdentifier])
               // But there is no foreign record with the same identifier AND the same uid
               && !isset($identifierList[$localIdentifier][$localUid])
               // The foreign part of the local record does not exists OR is temporary (= Not index for a remote file)
               && (!$file->foreignRecordExists()
                   || true === $file->getAdditionalProperty('foreignRecordExistsTemporary'))
               // The local record is not temporary (= he local record is persisted)
               && true !== $file->getAdditionalProperty('localRecordExistsTemporary');
    }

    /**
     * @param Record[] $files
     * @return Record[]
     */
    protected function mergeSysFileByIdentifier(array $files)
    {
        $identifierList = $this->buildFileListOfMissingLocalIndices($files);

        foreach ($files as $file) {
            if ($this->isLocalIndexWithMatchingDuplicateIndexOnForeign($identifierList, $file)) {
                $identifierList[$file->getLocalProperty('identifier')][$file->getLocalProperty('uid')] = $file;
            }
        }

        // only support sys_files with exactly one duplicate
        foreach ($identifierList as $identifierString => $fileEntries) {
            if (2 !== count($fileEntries)) {
                unset($identifierList[$identifierString]);
            }
        }

        foreach ($identifierList as $identifierString => $fileEntries) {
            // the first file is always the foreign file.
            $foreignFile = array_shift($fileEntries);
            $localFile = array_pop($fileEntries);
            $oldUid = (int)$foreignFile->getForeignProperty('uid');
            $newUid = (int)$localFile->getLocalProperty('uid');

            $logData = array('old' => $oldUid, 'new' => $newUid, 'identifier' => $identifierString);

            // Run the integrity test when enableSysFileReferenceUpdate (ESFRU) is not enabled
            if (true !== $this->configuration['enableSysFileReferenceUpdate']) {
                // If the sys_file was referenced abort here, because it's unsafe to overwrite the uid
                if (0 !== $this->countForeignReferences($oldUid)) {
                    break;
                }
            }

            // If a sys_file record with the "new" uid has been found abort immediately
            if (0 !== $this->countForeignIndices($newUid)) {
                break;
            }

            // Rewrite the foreign UID of the foreign index.
            if (true === $this->updateForeignIndex($oldUid, $newUid)) {
                $this->logger->notice('Rewrote a sys_file uid by the mergeSysFileByIdentifier feature', $logData);

                // Rewrite all occurrences of the old uid by the new in all references on foreign if SFRU is enabled
                if (true === $this->configuration['enableSysFileReferenceUpdate']) {
                    if (true === $this->updateForeignReference($oldUid, $newUid)) {
                        $this->logger->notice('Rewrote sys_file_reference by the SFRU feature', $logData);
                    } else {
                        $this->logger->error(
                            'Failed to rewrite sys_file_reference by the SFRU feature',
                            $this->enrichWithForeignDatabaseErrorInformation($logData)
                        );
                    }
                }

                // copy the foreign's properties with the new uid to the local record (merge)
                $foreignProperties = $foreignFile->getForeignProperties();
                $foreignProperties['uid'] = $newUid;
                $localFile->setForeignProperties($foreignProperties);
                $localFile->setDirtyProperties()->calculateState();

                // remove the (old) foreign file from the list
                unset($files[$oldUid]);
            } else {
                $this->logger->error(
                    'Failed to rewrite a sys_file uid by the mergeSysFileByIdentifier feature',
                    $this->enrichWithForeignDatabaseErrorInformation($logData)
                );
            }
        }

        return $files;
    }

    /**
     * @param array $diskIdentifiers
     * @param array $indexedIdentifiers
     * @param Record[] $files
     * @return Record[]
     */
    protected function fixAndConvertIntersectingIdentifiers(
        array $diskIdentifiers,
        array $indexedIdentifiers,
        array $files
    ) {
        // Create temporary indices for files existing on the local disk and foreign database
        $files = $this->fixAndConvertIntersectingIdentifiersIntoRecords(
            $diskIdentifiers,
            $indexedIdentifiers,
            $files,
            'local'
        );

        // Create temporary indices for files existing on the foreign disk and local database
        $files = $this->fixAndConvertIntersectingIdentifiersIntoRecords(
            $diskIdentifiers,
            $indexedIdentifiers,
            $files,
            'foreign'
        );
        return $files;
    }

    /**
     * @param array $onlyDiskIdentifiers
     * @param Record[] $files
     * @return Record[]
     */
    protected function convertAndAddOnlyDiskIdentifiersToFileRecords(array $onlyDiskIdentifiers, array $files)
    {
        // PRE-FIX for the [1] OLFS case; Create temporary sys_file entries for all files on the local disk
        $files = $this->convertAndAddOnlyDiskIdentifiersToFileRecordsBySide($onlyDiskIdentifiers, $files, 'local');

        // PRE-FIX for the [2] OFFS case; Create temporary sys_file entries for all files on the foreign disk
        $files = $this->convertAndAddOnlyDiskIdentifiersToFileRecordsBySide($onlyDiskIdentifiers, $files, 'foreign');
        return $files;
    }

    /**
     * @param array $indexedIdentifiers
     * @param array $diskIdentifiers
     * @param Record[] $files
     * @return Record[]
     */
    protected function indexFilesWithMissingIndexOnOneSide(
        array $indexedIdentifiers,
        array $diskIdentifiers,
        array $files
    ) {
        // Get a list of all identifiers that exist on both disks bot only in one database
        $indicesToRecheck = array_intersect($indexedIdentifiers['local'], $diskIdentifiers['both'])
                            + array_intersect($indexedIdentifiers['foreign'], $diskIdentifiers['both']);

        // PRE-FIXES
        foreach ($indicesToRecheck as $index => $identifier) {
            $file = $files[$index];
            $recordState = $file->getState();
            if (RecordInterface::RECORD_STATE_ADDED === $recordState) {
                // PRE-FIX for the [10] NFDB case
                // The file has been found on both file systems but only in the local database.
                // create a temporary counterpart for the local database entry, so we end up in [14] ALL
                $this->createAndAddTemporaryIndexInformationToRecordForSide($file, $identifier, 'foreign');
            } elseif (RecordInterface::RECORD_STATE_DELETED === $recordState) {
                // PRE-FIX for [13] NLDB
                // The file has been found on both file systems but not in the local database.
                // create a temporary local database entry with the uid of the existing foreign database entry.
                // Resulting case is [14] ALL
                $this->createAndAddTemporaryIndexInformationToRecordForSide($file, $identifier, 'local');
            }
        }

        return $files;
    }

    /**
     * @param array $logData
     * @return array
     */
    protected function enrichWithForeignDatabaseErrorInformation(array $logData)
    {
        return array_merge(
            $logData,
            array('error' => $this->foreignDatabase->sql_error(), 'errno' => $this->foreignDatabase->sql_errno())
        );
    }

    /**
     * @param int $oldUid
     * @param int $newUid
     * @return bool
     */
    protected function updateForeignIndex($oldUid, $newUid)
    {
        return (bool)$this->foreignDatabase->exec_UPDATEquery('sys_file', 'uid=' . $oldUid, array('uid' => $newUid));
    }

    /**
     * @param int $oldUid
     * @param int $newUid
     * @return bool
     */
    protected function updateForeignReference($oldUid, $newUid)
    {
        return (bool)$this->foreignDatabase->exec_UPDATEquery(
            'sys_file_reference',
            'table_local LIKE "sys_file" AND uid_local=' . (int)$oldUid,
            array('uid_local' => $newUid)
        );
    }

    /**
     * @param int $oldUid
     * @return int
     */
    protected function countForeignReferences($oldUid)
    {
        $count = $this->foreignDatabase->exec_SELECTcountRows(
            'uid',
            'sys_file_reference',
            'table_local LIKE "sys_file" AND uid_local=' . $oldUid
        );
        if (false === $count) {
            $this->logger->critical(
                'Could not count foreign references by uid',
                $this->enrichWithForeignDatabaseErrorInformation(array('uid', $oldUid))
            );
            throw new \RuntimeException('Could not count foreign references by uid', 1476097402);
        }
        return (int)$count;
    }

    /**
     * @param int $newUid
     * @return int
     */
    protected function countForeignIndices($newUid)
    {
        $count = $this->foreignDatabase->exec_SELECTcountRows('uid', 'sys_file', 'uid=' . $newUid);
        if (false === $count) {
            $this->logger->critical(
                'Could not count foreign indices by uid',
                $this->enrichWithForeignDatabaseErrorInformation(array('uid', $newUid))
            );
            throw new \RuntimeException('Could not count foreign indices by uid', 1476097373);
        }
        return (int)$count;
    }

    /**
     * @param Record[] $files
     * @return Record[][]
     */
    protected function buildFileListOfMissingLocalIndices(array $files)
    {
        $identifierList = array();
        // Get all foreign file identifiers to match again
        foreach ($files as $file) {
            // If the file only exist local skip it.
            $foreignIdentifier = $file->getForeignProperty('identifier');
            if (null === $foreignIdentifier || !$file->hasForeignProperty('uid')) {
                continue;
            }

            // If the local record exists AND is not temporary skip this file. (= Does not index a local file)
            if ($file->localRecordExists() && true !== $file->getAdditionalProperty('localRecordExistsTemporary')) {
                continue;
            }

            // If the foreign record is temporary skip this file.
            if (true === $file->getAdditionalProperty('foreignRecordExistsTemporary')) {
                continue;
            }

            $identifierList[$foreignIdentifier][$file->getIdentifier()] = $file;
        }
        return $identifierList;
    }

    /**
     * @param DriverInterface $driver
     * @param string $subFolderIdentifier
     * @return array
     */
    protected function getFolderInfoByDriverAndIdentifier(DriverInterface $driver, $subFolderIdentifier)
    {
        if ($driver->folderExists($subFolderIdentifier)) {
            $folderInfo = $driver->getFolderInfoByIdentifier($subFolderIdentifier);
            $folderInfo['uid'] = $this->createCombinedIdentifier($folderInfo);
        } else {
            $folderInfo = array();
        }
        return $folderInfo;
    }
}
