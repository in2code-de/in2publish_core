<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\FalHandling\Finder;

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

use In2code\In2publishCore\Component\FalHandling\FalFinder;
use In2code\In2publishCore\Component\FalHandling\Finder\Exception\TooManyFilesException;
use In2code\In2publishCore\Component\FalHandling\Finder\Exception\TooManyForeignFilesException;
use In2code\In2publishCore\Component\FalHandling\Finder\Exception\TooManyLocalFilesException;
use In2code\In2publishCore\Component\RecordHandling\RecordFinder;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Driver\RemoteStorage;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Event\FolderInstanceWasCreated;
use In2code\In2publishCore\Utility\FileUtility;
use In2code\In2publishCore\Utility\FolderUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function array_merge;
use function array_unique;
use function count;
use function dirname;
use function in_array;
use function strpos;
use function substr;

/**
 * This class describes an alternative workflow for the FAL diff and publishing mechanism.
 * It's solely used for the reserveSysFileUids feature.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexingFalFinder implements FalFinder
{
    /** @var int Maximum number of files which are supported to exist in a single folder */
    protected int $threshold;

    protected RemoteStorage $remoteStorage;

    protected ResourceFactory $resourceFactory;

    protected RecordFinder $recordFinder;

    protected ResourceStorage $localStorage;

    protected EventDispatcher $eventDispatcher;

    public function __construct(
        ConfigContainer $configContainer,
        RemoteStorage $remoteStorage,
        ResourceFactory $resourceFactory,
        RecordFinder $recordFinder,
        EventDispatcher $eventDispatcher
    ) {
        $this->threshold = $configContainer->get('factory.fal.folderFileLimit');
        $this->remoteStorage = $remoteStorage;
        $this->resourceFactory = $resourceFactory;
        $this->recordFinder = $recordFinder;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function overruleLocalStorage(ResourceStorage $localStorage): void
    {
        $this->localStorage = $localStorage;
    }

    public function overruleRemoteStorage(RemoteStorage $remoteStorage): void
    {
        $this->remoteStorage = $remoteStorage;
    }

    /**
     * @param string|null $dir Directory which is currently selected in the directory tree
     *
     * @return RecordInterface
     *
     * @throws TooManyFilesException
     * @throws InsufficientFolderAccessPermissionsException
     */
    public function findFalRecord(?string $dir): RecordInterface
    {
        // determine current folder
        try {
            if (null === $dir) {
                $localFolder = $this->resourceFactory->getDefaultStorage()->getRootLevelFolder();
            } else {
                $localFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($dir);
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (FolderDoesNotExistException $exception) {
            $resourceStorage = $this->resourceFactory->getStorageObject(substr($dir, 0, strpos($dir, ':')));
            $localFolder = $resourceStorage->getRootLevelFolder();
        }

        // get FAL storages for each side
        $this->localStorage = $localFolder->getStorage();

        // some often used variables
        $storageUid = $this->localStorage->getUid();
        $folderIdentifier = $localFolder->getIdentifier();

        // gather information about the folder, sub folders and files in this folder
        $localProperties = FolderUtility::extractFolderInformation($localFolder);
        $remoteProperties = [];
        $localSubFolders = FolderUtility::extractFoldersInformation($localFolder->getSubfolders());
        $remoteSubFolders = [];
        $localFiles = FileUtility::extractFilesInformation($this->localStorage->getFilesInFolder($localFolder));

        $this->checkFileCount($localFiles, $folderIdentifier, 'local');

        $remoteFiles = [];

        // get the actual information from remote if the folder actually exists
        if (true === $this->remoteStorage->hasFolder($storageUid, $folderIdentifier)) {
            $remoteProperties = $localProperties;
            $remoteSubFolders = $this->remoteStorage->getFoldersInFolder($storageUid, $folderIdentifier);
            $remoteFiles = $this->remoteStorage->getFilesInFolder($storageUid, $folderIdentifier);
        }

        $this->checkFileCount($remoteFiles, $folderIdentifier, 'foreign');

        $rootFolder = GeneralUtility::makeInstance(
            Record::class,
            'physical_folder',
            $localProperties,
            $remoteProperties,
            [],
            ['depth' => 1]
        );

        $folderIdentifiers = array_unique(array_merge(array_keys($localSubFolders), array_keys($remoteSubFolders)));
        foreach ($folderIdentifiers as $identifier) {
            $subFolder = GeneralUtility::makeInstance(
                Record::class,
                'physical_folder',
                $localSubFolders[$identifier] ?? [],
                $remoteSubFolders[$identifier] ?? [],
                [],
                ['depth' => 2]
            );
            $rootFolder->addRelatedRecord($subFolder);
        }

        $properties = ['folder_hash' => $localFolder->getHashedIdentifier(), 'storage' => $storageUid];
        $records = $this->recordFinder->findRecordsByProperties($properties, 'sys_file', true);
        $records = $this->filterRecords($localFiles, $remoteFiles, $records);
        $rootFolder->addRelatedRecords($records);

        $this->eventDispatcher->dispatch(new FolderInstanceWasCreated($rootFolder));

        return $rootFolder;
    }

    /**
     * Detects files which are located in other folders that the local/foreign one (e.g. the local folder got renamed)
     *
     * @param RecordInterface[] $records
     * @param array $files
     * @param string $side
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function updateFilesByMovedRecords(array $records, array $files, string $side): array
    {
        $relatedFolders = [];

        foreach ($records as $record) {
            if ($record->getState() === RecordInterface::RECORD_STATE_MOVED) {
                $fileIdentifier = $record->getPropertyBySideIdentifier($side, 'identifier');
                $folder = dirname($fileIdentifier);
                if (!isset($relatedFolders[$folder])) {
                    $relatedFolders[$folder] = [];
                    $relatedFolders[$folder]['files'] = [];
                    $relatedFolders[$folder]['storageUid'] = $record->getPropertyBySideIdentifier($side, 'storage');
                }
                $relatedFolders[$folder]['files'][] = $fileIdentifier;
            }
        }

        if (!empty($relatedFolders)) {
            if ($side === 'foreign') {
                $storage = $this->remoteStorage;
            } else {
                $storage = $this->localStorage;
            }
            if ($storage instanceof ResourceStorage) {
                $evaluatePermissions = $storage->getEvaluatePermissions();
                $storage->setEvaluatePermissions(false);
            }
            foreach ($relatedFolders as $folder => $fileInfo) {
                if ($side === 'foreign') {
                    if ($storage->hasFolder((int)$fileInfo['storageUid'], $folder)) {
                        $filesInFolder = $storage->getFilesInFolder((int)$fileInfo['storageUid'], $folder);
                        foreach ($fileInfo['files'] as $identifier) {
                            if (isset($filesInFolder[$identifier])) {
                                $files[$identifier] = $filesInFolder[$identifier];
                            }
                        }
                    }
                } elseif ($storage->hasFolder($folder)) {
                    $filesInFolder = $storage->getFolder($folder)->getFiles();
                    $filesInFolder = FileUtility::extractFilesInformation($filesInFolder);
                    foreach ($fileInfo['files'] as $identifier) {
                        if (isset($filesInFolder[$identifier])) {
                            $files[$identifier] = $filesInFolder[$identifier];
                        }
                    }
                }
            }
            if (isset($evaluatePermissions) && $storage instanceof ResourceStorage) {
                $storage->setEvaluatePermissions($evaluatePermissions);
            }
        }

        return $files;
    }

    /**
     * Remove properties from a side where a file does not exist
     * or remove the whole record from the list if there is no file at all
     *
     * @param array $localFiles
     * @param array $remoteFiles
     * @param RecordInterface[] $records
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function filterRecords(array $localFiles, array $remoteFiles, array $records): array
    {
        $filesOnDisk = array_unique(array_merge(array_keys($localFiles), array_keys($remoteFiles)));

        /** @var RecordInterface[] $touchedEntries */
        $touchedEntries = [];

        $localFiles = $this->updateFilesByMovedRecords($records, $localFiles, 'local');
        $remoteFiles = $this->updateFilesByMovedRecords($records, $remoteFiles, 'foreign');

        foreach ($records as $index => $file) {
            $localFileName = $file->hasLocalProperty('identifier') ? $file->getLocalProperty('identifier') : '';
            $foreignFileName = $file->hasForeignProperty('identifier') ? $file->getForeignProperty('identifier') : '';

            // remove records from the list which do not have at least one file on the disk which they represent
            if (!in_array($localFileName, $filesOnDisk) && !in_array($foreignFileName, $filesOnDisk)) {
                unset($records[$index]);
            } else {
                if ($file->getState() === RecordInterface::RECORD_STATE_MOVED) {
                    $fileInfoIndex = $localFileName;
                } else {
                    $fileInfoIndex = $localFileName !== '' ? $localFileName : $foreignFileName;
                    $localFileName = $localFileName !== '' ? $localFileName : $fileInfoIndex;
                    $foreignFileName = $foreignFileName !== '' ? $foreignFileName : $fileInfoIndex;
                }
                if (isset($touchedEntries[$fileInfoIndex])) {
                    $touchedEntries[$fileInfoIndex]->addAdditionalProperty('isPrimaryIndex', true);
                    $file->addAdditionalProperty('isDuplicateIndex', true);
                    unset($records[$index]);
                } else {
                    $touchedEntries[$fileInfoIndex] = $file;

                    $fileExistsLocally = isset($localFiles[$localFileName]);
                    $fileExistsRemotely = isset($remoteFiles[$foreignFileName]);

                    // save the database state separately, because we're going to modify it now.
                    $file->addAdditionalProperty('recordDatabaseState', $file->getState());

                    // if the file exists on disk then overrule the index data with the file information
                    if (true === $fileExistsLocally) {
                        $localProperties = $file->getLocalProperties();
                        if (!empty($localProperties)) {
                            ArrayUtility::mergeRecursiveWithOverrule(
                                $localProperties,
                                $localFiles[$localFileName],
                                false
                            );
                        }
                    } else {
                        // truncate the indexed values if the represented file does not exist on disk
                        $localProperties = [];
                    }
                    // do it again for foreign
                    if (true === $fileExistsRemotely) {
                        $foreignProperties = $file->getForeignProperties();
                        ArrayUtility::mergeRecursiveWithOverrule($foreignProperties, $remoteFiles[$foreignFileName]);
                    } else {
                        $foreignProperties = [];
                    }

                    $file->setLocalProperties($localProperties);
                    $file->setForeignProperties($foreignProperties);
                    $file->setDirtyProperties()->calculateState();

                    // mark the file state as desired publishing action for the PhysicalFilePublisherAnomaly.
                    $file->addAdditionalProperty('isAuthoritative', true);
                }
            }
        }
        return $records;
    }

    /** @throws TooManyFilesException */
    protected function checkFileCount(array $files, string $folderIdentifier, string $side): void
    {
        $count = count($files);
        if ($count > $this->threshold) {
            if ($side === 'foreign') {
                throw new TooManyForeignFilesException($folderIdentifier, $count, $this->threshold);
            }
            throw new TooManyLocalFilesException($folderIdentifier, $count, $this->threshold);
        }
    }
}
