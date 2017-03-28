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
        $resourceFactory = ResourceFactory::getInstance();
        try {
            $localFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier($dir);
        } catch (FolderDoesNotExistException $exception) {
            $localFolder = $resourceFactory->getStorageObject(substr($dir, 0, strpos($dir, ':')))->getRootLevelFolder();
        }
        $localStorage = $localFolder->getStorage();

        $localProperties = FolderUtility::extractFolderInformation($localFolder);
        $localSubFolders = FolderUtility::extractFoldersInformation($localFolder->getSubfolders());

        $remoteStorage = GeneralUtility::makeInstance('In2code\\In2publishCore\\Domain\\Driver\\RemoteStorage');
        $remoteFolderExists = $remoteStorage->hasFolder($localStorage->getUid(), $localFolder->getIdentifier());

        if (false === $remoteFolderExists) {
            $remoteProperties = array();
            $remoteSubFolders = array();
            $remoteFiles = array();
        } else {
            $remoteProperties = $localProperties;
            $remoteSubFolders = $remoteStorage->getFoldersInFolder(
                $localStorage->getUid(),
                $localFolder->getIdentifier()
            );
            $remoteFiles = $remoteStorage->getFilesInFolder($localStorage->getUid(), $localFolder->getIdentifier());
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

        $localFiles = FileUtility::extractFilesInformation($localStorage->getFilesInFolder($localFolder));

        $fileIdentifiers = array_unique(array_merge(array_keys($localFiles), array_keys($remoteFiles)));

        $files = CommonRepository::getDefaultInstance('sys_file')->findByProperties(
            array('folder_hash' => $localFolder->getHashedIdentifier(), 'storage' => $localStorage->getUid()),
            true
        );

        foreach ($files as $index => $file) {
            if ($file->hasLocalProperty('identifier')) {
                if (in_array($file->getLocalProperty('identifier'), $fileIdentifiers)) {
                    continue;
                }
            }
            if ($file->hasForeignProperty('identifier')) {
                if (in_array($file->getForeignProperty('identifier'), $fileIdentifiers)) {
                    continue;
                }
            }
            unset($files[$index]);
        }

        $rootFolder->addRelatedRecords($files);

        return $rootFolder;
    }
}
