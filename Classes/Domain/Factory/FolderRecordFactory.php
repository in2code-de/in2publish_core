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
            switch ($file->getState()) {
                case RecordInterface::RECORD_STATE_ADDED:
                    // Only in LDB, therefore not publishable
                    if (!$localDriver->fileExists($file->getLocalProperty('identifier'))) {
                        unset($files[$index]);
                        continue;
                    }
                    break;
                case RecordInterface::RECORD_STATE_DELETED:
                    // Only in FDB, therefore not publishable
                    if (!$foreignDriver->fileExists($file->getForeignProperty('identifier'))) {
                        unset($files[$index]);
                        continue;
                    }
                    break;
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
     * @param $subFolderIdentifiers
     * @param $localDriver
     * @param $foreignDriver
     * @return array
     */
    protected function getSubFolders($subFolderIdentifiers, $localDriver, $foreignDriver)
    {
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
