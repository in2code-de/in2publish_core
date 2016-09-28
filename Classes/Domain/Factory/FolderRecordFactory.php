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
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * FolderRecordFactory constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
    }

    /**
     * Only work with drivers so we don't "accidentally" index files...
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
         */
        $resourceFactory = ResourceFactory::getInstance();

        if (null === $identifier) {
            $localStorage = $resourceFactory->getDefaultStorage();
            $localFolder = $localStorage->getRootLevelFolder();
        } else {
            $localFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier($identifier);
            $localStorage = $localFolder->getStorage();
        }

        $localDriver = $this->getLocalDriver($localStorage);
        $foreignDriver = $this->getForeignDriver($localStorage);

        $identifier = $localFolder->getIdentifier();
        $localFolderInfo = $localDriver->getFolderInfoByIdentifier($identifier);
        $localFolderInfo['uid'] = $this->createCombinedIdentifier($localFolderInfo);

        $localSubFolders = $localDriver->getFoldersInFolder($identifier);

        if ($foreignDriver->folderExists($localFolder->getIdentifier())) {
            $foreignFolderInfo = $foreignDriver->getFolderInfoByIdentifier($localFolder->getIdentifier());
            $foreignFolderInfo['uid'] = $this->createCombinedIdentifier($foreignFolderInfo);
            $remoteSubFolders = $foreignDriver->getFoldersInFolder($identifier);
        } else {
            $foreignFolderInfo = array();
            $remoteSubFolders = array();
        }

        $record = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Model\\Record',
            'physical_folder',
            $localFolderInfo,
            $foreignFolderInfo,
            array(),
            array('depth' => 1)
        );

        $subFolders = $this->getSubFolders(
            array_merge($localSubFolders, $remoteSubFolders),
            $localDriver,
            $foreignDriver
        );

        $record->addRelatedRecords($subFolders);

        $commonRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get(
            'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
            DatabaseUtility::buildLocalDatabaseConnection(),
            DatabaseUtility::buildForeignDatabaseConnection(),
            'sys_file'
        );

        $files = $commonRepository->findByProperty('folder_hash', $localFolder->getHashedIdentifier());

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
            $lfs = $localDriver->fileExists($file->getLocalProperty('identifier'));
            $ffs = $foreignDriver->fileExists($file->getForeignProperty('identifier'));

            if ($ldb && !$lfs && !$ffs && !$fdb) {
                // CODE: [0] OLDB
                // The file exists only in the local database. Ignore the orphaned DB record.
                unset($files[$index]);
                continue;
            } elseif (!$ldb && $lfs && !$ffs && !$fdb) {
                // CODE: [1] OLFS
                // TODO
                // Create the local database entry by indexing the file
                // Assign the new information to the file and diff again
                // We end up in [4] OL
            } elseif (!$ldb && !$lfs && $ffs && !$fdb) {
                // CODE: [2] OFFS
                // TODO
                // Try to index the file on foreign and reassign the foreign info.
                // Diff again and end up in [9] OF
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
                // TODO
                // Okay i currently don't know how to handle this.
                // I think the best solution would be indexing the file on
                // foreign with the UID from the local database record.
                // That would lead us to [12] NLFS so at least it's one case less.
            } elseif ($ldb && !$lfs && !$ffs && $fdb) {
                // CODE: [6] ODB
                // So there are two orphans (db without fs). we could diff them, but there's no file to publish.
                // I've decided to just ignore this case, since publishing  would not have an effect on the file system
                // and additionally i consider these files deleted, as this is a result of [12] NLFS
                unset($files[$index]);
                continue;
            } elseif (!$ldb && $lfs && $ffs && !$fdb) {
                // CODE: [7] OFS
                // TODO
                // We have the files on both sides.
                // Index them on both sides with the same UID for the sys_file and add that info to the record
                // Conveniently we end up in [14] ALL. Yai!
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
                // TODO
                // The foreign database record is orphaned.
                // The file was clearly deleted on foreign or the database record was prematurely published
                // TODO determine if this is to be displayed as NEW or CHANGED
            } elseif ($ldb && !$lfs && $ffs && $fdb) {
                // CODE: [12] NLFS
                // TODO
                // The local database record is orphaned.
                // On foreign everything is okay.
                // Two cases: either the UID was assigned independent or the local file was removed
                // In both cases we will remove the remote file, because stage always wins.
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
     * @param array $subFolderIdentifiers
     * @param DriverInterface $localDriver
     * @param DriverInterface $foreignDriver
     * @return array
     */
    protected function getSubFolders(
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
}
