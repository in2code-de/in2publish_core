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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Utility\FileUtility;
use In2code\In2publishCore\Utility\FolderUtility;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class describes an alternative workflow for the FAL diff and publishing mechanism.
 * It's solely used for the reserveSysFileUids feature.
 */
class IndexingFolderRecordFactory
{
    /**
     * @param string|null $dir Directory which is currently selected in the directory tree
     * @return RecordInterface
     */
    public function makeInstance($dir = null)
    {
        // determine current folder
        $resourceFactory = ResourceFactory::getInstance();
        try {
            $localFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier($dir);
        } catch (FolderDoesNotExistException $exception) {
            $localFolder = $resourceFactory->getStorageObject(substr($dir, 0, strpos($dir, ':')))->getRootLevelFolder();
        }

        // get FAL storages for each side
        $localStorage = $localFolder->getStorage();
        $remoteStorage = GeneralUtility::makeInstance('In2code\\In2publishCore\\Domain\\Driver\\RemoteStorage');

        // some often used variables
        $storageUid = $localStorage->getUid();
        $folderIdentifier = $localFolder->getIdentifier();

        // gather information about the folder, sub folders and files in this folder
        $localProperties = FolderUtility::extractFolderInformation($localFolder);
        $remoteProperties = array();
        $localSubFolders = FolderUtility::extractFoldersInformation($localFolder->getSubfolders());
        $remoteSubFolders = array();
        $localFiles = FileUtility::extractFilesInformation($localStorage->getFilesInFolder($localFolder));
        $remoteFiles = array();

        // get the actual information from remote if the folder actually exists
        if (true === $remoteStorage->hasFolder($storageUid, $folderIdentifier)) {
            $remoteProperties = $localProperties;
            $remoteSubFolders = $remoteStorage->getFoldersInFolder($storageUid, $folderIdentifier);
            $remoteFiles = $remoteStorage->getFilesInFolder($storageUid, $folderIdentifier);
        }

        $rootFolder = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Model\\Record',
            'physical_folder',
            $localProperties,
            $remoteProperties,
            array(),
            array('depth' => 1)
        );

        $folderIdentifiers = array_unique(array_merge(array_keys($localSubFolders), array_keys($remoteSubFolders)));
        foreach ($folderIdentifiers as $identifier) {
            $subFolder = GeneralUtility::makeInstance(
                'In2code\\In2publishCore\\Domain\\Model\\Record',
                'physical_folder',
                isset($localSubFolders[$identifier]) ? $localSubFolders[$identifier] : array(),
                isset($remoteSubFolders[$identifier]) ? $remoteSubFolders[$identifier] : array(),
                array(),
                array('depth' => 2)
            );
            $rootFolder->addRelatedRecord($subFolder);
        }

        $records = CommonRepository::getDefaultInstance('sys_file')->findByProperties(
            array('folder_hash' => $localFolder->getHashedIdentifier(), 'storage' => $storageUid),
            true
        );
        $records = $this->filterRecords($localFiles, $remoteFiles, $records);
        $rootFolder->addRelatedRecords($records);

        return $rootFolder;
    }

    /**
     * Remove properties from a side where a file does not exist
     * or remove the whole record from the list if there is no file at all
     *
     * @param array $localFiles
     * @param array $remoteFiles
     * @param array $records
     * @return array
     */
    protected function filterRecords(array $localFiles, array $remoteFiles, array $records)
    {
        $filesOnDisk = array_unique(array_merge(array_keys($localFiles), array_keys($remoteFiles)));

        foreach ($records as $index => $file) {
            $localFileName = $file->hasLocalProperty('identifier') ? $file->getLocalProperty('identifier') : '';
            $foreignFileName = $file->hasForeignProperty('identifier') ? $file->getForeignProperty('identifier') : '';

            // remove records from the list which do not have at least one file on the disk which they represent
            if (!in_array($localFileName, $filesOnDisk) && !in_array($foreignFileName, $filesOnDisk)) {
                unset($records[$index]);
            } else {
                // save the database state separately, because we're going to modify it now.
                $file->addAdditionalProperty('recordDatabaseState', $file->getState());

                // if the file exists on disk then overrule the index data with the file information
                if (isset($localFiles[$localFileName])) {
                    $localProperties = $file->getLocalProperties();
                    ArrayUtility::mergeRecursiveWithOverrule($localProperties, $localFiles[$localFileName], false);
                } else {
                    // truncate the indexed values if the represented file does not exist on disk
                    $localProperties = array();
                }
                // do it again for foreign
                if (isset($remoteFiles[$foreignFileName])) {
                    $foreignProperties = $file->getLocalProperties();
                    ArrayUtility::mergeRecursiveWithOverrule($foreignProperties, $remoteFiles[$foreignFileName], false);
                } else {
                    $foreignProperties = array();
                }

                $file->setLocalProperties($localProperties);
                $file->setForeignProperties($foreignProperties);
                $file->setDirtyProperties()->calculateState();

                // mark the file state as desired publishing action for the PhysicalFilePublisherAnomaly.
                $file->addAdditionalProperty('isAuthoritative', true);
            }
        }
        return $records;
    }
}
