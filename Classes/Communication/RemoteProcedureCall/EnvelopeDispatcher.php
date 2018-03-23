<?php
namespace In2code\In2publishCore\Communication\RemoteProcedureCall;

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

use In2code\In2publishCore\Domain\Driver\RemoteStorage;
use In2code\In2publishCore\Domain\Factory\FileIndexFactory;
use In2code\In2publishCore\Utility\FileUtility;
use In2code\In2publishCore\Utility\FolderUtility;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class EnvelopeDispatcher
 */
class EnvelopeDispatcher
{
    /*
     * Non-indexing commands
     */
    const CMD_FOLDER_EXISTS = 'folderExists';
    const CMD_FILE_EXISTS = 'fileExists';
    const CMD_GET_PERMISSIONS = 'getPermissions';
    const CMD_GET_FOLDERS_IN_FOLDER = 'getFoldersInFolder';
    const CMD_GET_FILES_IN_FOLDER = 'getFilesInFolder';
    const CMD_GET_FILE_INFO_BY_IDENTIFIER = 'getFileInfoByIdentifier';
    const CMD_GET_HASH = 'hash';
    const CMD_CREATE_FOLDER = 'createFolder';
    const CMD_DELETE_FOLDER = 'deleteFolder';
    const CMD_DELETE_FILE = 'deleteFile';
    const CMD_ADD_FILE = 'addFile';
    const CMD_REPLACE_FILE = 'replaceFile';
    const CMD_RENAME_FILE = 'renameFile';
    const CMD_GET_PUBLIC_URL = 'getPublicUrl';
    const CMD_BATCH_PREFETCH_FILES = 'batchPrefetchFiles';
    const CMD_MOVE_FILE_WITHIN_STORAGE = 'moveFileWithinStorage';
    /*
     * Indexing commands (using the storage object)
     */
    const CMD_STORAGE_HAS_FOLDER = 'getStorageHasFolder';
    const CMD_STORAGE_GET_FOLDERS_IN_FOLDER = 'getStorageGetFoldersInFolder';
    const CMD_STORAGE_GET_FILES_IN_FOLDER = 'getStorageGetFilesInFolder';
    const CMD_STORAGE_GET_FILE = 'getStorageGetFile';
    /*
     * Others
     */
    const CMD_GET_SET_DB_INIT = 'getSetDbInit';

    /**
     * Limits the amount of files in a folder for pre fetching. If there are more than $prefetchLimit files in
     * the selected folder they will not be processed when not requested explicitly.
     *
     * @var int
     */
    protected $prefetchLimit = 51;

    /**
     * @param Envelope $envelope
     * @return bool
     */
    public function dispatch(Envelope $envelope)
    {
        $command = $envelope->getCommand();
        if (method_exists($this, $command)) {
            $envelope->setResponse(call_user_func([$this, $command], $envelope->getRequest()));
            return true;
        }

        return false;
    }

    /**
     * @param array $request
     * @return array
     */
    protected function folderExists(array $request)
    {
        $storage = $this->getStorage($request);
        $driver = $this->getStorageDriver($storage);
        $folderIdentifier = $request['folderIdentifier'];

        if ($driver->folderExists($folderIdentifier)) {
            $files = [];

            if (is_callable([$driver, 'countFilesInFolder'])
                && $driver->countFilesInFolder($folderIdentifier) < $this->prefetchLimit
            ) {
                $fileIdentifiers = $this->convertIdentifiers($driver, $driver->getFilesInFolder($folderIdentifier));

                foreach ($fileIdentifiers as $fileIdentifier) {
                    $fileObject = $this->getFileObject($driver, $fileIdentifier, $storage);
                    $files[$fileIdentifier] = [];
                    $files[$fileIdentifier]['hash'] = $driver->hash($fileIdentifier, 'sha1');
                    $files[$fileIdentifier]['info'] = $driver->getFileInfoByIdentifier($fileIdentifier);
                    $files[$fileIdentifier]['publicUrl'] = $storage->getPublicUrl($fileObject);
                }
            }

            return [
                'exists' => true,
                'folders' => $this->convertIdentifiers($driver, $driver->getFoldersInFolder($folderIdentifier)),
                'files' => $files,
            ];
        }

        return [
            'exists' => false,
            'folders' => [],
            'files' => [],
        ];
    }

    /**
     * @param array $request
     * @return array
     */
    protected function getPermissions(array $request)
    {
        $storage = $this->getStorage($request);
        return $this->getStorageDriver($storage)->getPermissions($request['identifier']);
    }

    /**
     * @param array $request
     * @return array
     */
    protected function getFoldersInFolder(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return $this->convertIdentifiers($driver, call_user_func_array([$driver, 'getFoldersInFolder'], $request));
    }

    /**
     * @param array $request
     * @return array
     */
    protected function fileExists(array $request)
    {
        $storage = $this->getStorage($request);
        $driver = $this->getStorageDriver($storage);

        $fileIdentifier = $request['fileIdentifier'];
        $directory = PathUtility::dirname($fileIdentifier);
        if ($driver->folderExists($directory)) {
            if (is_callable([$driver, 'countFilesInFolder'])
                && $driver->countFilesInFolder($directory) < $this->prefetchLimit
            ) {
                $files = $this->convertIdentifiers(
                    $driver,
                    call_user_func([$driver, 'getFilesInFolder'], $directory)
                );
                if (is_array($files)) {
                    foreach ($files as $file) {
                        $fileObject = $this->getFileObject($driver, $file, $storage);
                        $files[$file] = [];
                        $files[$file]['hash'] = $driver->hash($file, 'sha1');
                        $files[$file]['info'] = $driver->getFileInfoByIdentifier($file);
                        $files[$file]['publicUrl'] = $storage->getPublicUrl($fileObject);
                    }
                }
            } else {
                $fileObject = $this->getFileObject($driver, $fileIdentifier, $storage);
                if (null !== $fileObject) {
                    $files = [
                        $fileIdentifier => [
                            'hash' => $driver->hash($fileIdentifier, 'sha1'),
                            'info' => $driver->getFileInfoByIdentifier($fileIdentifier),
                            'publicUrl' => $storage->getPublicUrl($fileObject),
                        ],
                    ];
                } else {
                    $files = [];
                }
            }
            return $files;
        }
        return [];
    }

    /**
     * @param array $request
     * @return array
     */
    protected function getFilesInFolder(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        $files = $this->convertIdentifiers($driver, call_user_func_array([$driver, 'getFilesInFolder'], $request));

        foreach ($files as $file) {
            $fileObject = $this->getFileObject($driver, $file, $storage);
            $files[$file] = [];
            $files[$file]['hash'] = $driver->hash($file, 'sha1');
            $files[$file]['info'] = $driver->getFileInfoByIdentifier($file);
            $files[$file]['publicUrl'] = $storage->getPublicUrl($fileObject);
        }
        return $files;
    }

    /**
     * @param array $request
     * @return array
     */
    protected function getFileInfoByIdentifier(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array([$driver, 'getFileInfoByIdentifier'], $request);
    }

    /**
     * @param array $request
     * @return array
     */
    protected function hash(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array([$driver, 'hash'], $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function createFolder(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array([$driver, 'createFolder'], $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function deleteFolder(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array([$driver, 'deleteFolder'], $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function deleteFile(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        if ($driver->fileExists($request['fileIdentifier'])) {
            return $driver->deleteFile($request['fileIdentifier']);
        }
        return true;
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function addFile(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array([$driver, 'addFile'], $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function replaceFile(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array([$driver, 'replaceFile'], $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function renameFile(array $request)
    {
        $storage = $this->getStorage($request);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array([$driver, 'renameFile'], $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function getPublicUrl(array $request)
    {
        $storage = $this->getStorage($request);
        $driver = $this->getStorageDriver($storage);
        $identifier = $request['identifier'];
        $file = $this->getFileObject($driver, $identifier, $storage);
        return $storage->getPublicUrl($file);
    }

    /**
     * @param array $request
     * @return array
     */
    protected function batchPrefetchFiles(array $request)
    {
        $storage = $this->getStorage($request);
        $storage->setEvaluatePermissions(false);
        $driver = $this->getStorageDriver($storage);

        $files = [];

        foreach ($request['identifiers'] as $identifier) {
            if ($driver->fileExists($identifier)) {
                $fileObject = $this->getFileObject($driver, $identifier, $storage);
                $files[$identifier] = [];
                $files[$identifier]['hash'] = $driver->hash($identifier, 'sha1');
                $files[$identifier]['info'] = $driver->getFileInfoByIdentifier($identifier);
                $files[$identifier]['publicUrl'] = $storage->getPublicUrl($fileObject);
            }
        }

        return $files;
    }

    /**
     * @param array $request
     * @return string
     */
    protected function moveFileWithinStorage(array $request)
    {
        $storage = $this->getStorage($request);
        $driver = $this->getStorageDriver($storage);
        $targetDirName = $request['targetFolderIdentifier'];

        // ensure directory exists before moving file into it
        if (!$driver->folderExists($targetDirName)) {
            $driver->createFolder(basename($targetDirName), dirname($targetDirName));
        }

        return $driver->moveFileWithinStorage(
            $request['fileIdentifier'],
            $targetDirName,
            $request['newFileName']
        );
    }

    /**
     * @param ResourceStorage $storage
     * @return DriverInterface
     */
    protected function getStorageDriver(ResourceStorage $storage)
    {
        $driverReflection = new \ReflectionProperty(get_class($storage), 'driver');
        $driverReflection->setAccessible(true);
        /** @var DriverInterface $driver */
        return $driverReflection->getValue($storage);
    }

    /**
     * @param $driver
     * @param $identifier
     * @param $storage
     * @return File|null|object
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getFileObject($driver, $identifier, $storage)
    {
        $fileIndexFactory = GeneralUtility::makeInstance(FileIndexFactory::class, $driver, $driver);

        /** @var File $file */
        $fileIndexArray = $fileIndexFactory->getFileIndexArray($identifier, 'local');
        if (empty($fileIndexArray)) {
            return null;
        }
        return GeneralUtility::makeInstance(File::class, $fileIndexArray, $storage);
    }

    /**
     * @param DriverInterface $driver
     * @param array $identifierList
     * @return array
     */
    protected function convertIdentifiers(DriverInterface $driver, array $identifierList)
    {
        if (!$driver->isCaseSensitiveFileSystem()) {
            $identifierList = array_map(
                function ($identifier) {
                    return strtolower($identifier);
                },
                $identifierList
            );
        }
        return $identifierList;
    }

    /**
     * @param array $request
     * @return ResourceStorage
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getStorage(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        $storage->setEvaluatePermissions(false);
        return $storage;
    }

    /**
     * @param array $request
     * @return array
     */
    public function getStorageHasFolder(array $request)
    {
        $hasFolder = $this->getStorage($request)->hasFolder($request['identifier']);
        // pre-fetching sub folders and files for later use if storage exists (we know this will be required)
        if ($hasFolder) {
            $subFolders = $this->getStorageGetFoldersInFolder($request);
            $files = $this->getStorageGetFilesInFolder($request);
        } else {
            $subFolders = [];
            $files = [];
        }
        return [
            RemoteStorage::HAS_FOLDER_KEY => $hasFolder,
            RemoteStorage::SUB_FOLDERS_KEY => $subFolders,
            RemoteStorage::FILES_KEY => $files,
        ];
    }

    /**
     * @param array $request
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getStorageGetFoldersInFolder(array $request)
    {
        $storage = $this->getStorage($request);
        $folders = $storage->getFoldersInFolder($storage->getFolder($request['identifier']));
        return FolderUtility::extractFoldersInformation($folders);
    }

    /**
     * @param array $request
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getStorageGetFilesInFolder(array $request)
    {
        $storage = $this->getStorage($request);
        $files = $storage->getFilesInFolder($storage->getFolder($request['identifier']));
        return FileUtility::extractFilesInformation($files);
    }

    /**
     * @param array $request
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getStorageGetFile(array $request)
    {
        $storage = $this->getStorage($request);
        if ($storage->hasFile($request['identifier'])) {
            $file = $storage->getFile($request['identifier']);
            return FileUtility::extractFileInformation($file);
        }
        return [];
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getSetDbInit()
    {
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'])) {
            return $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'];
        } elseif (!empty($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'])) {
            return $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'];
        }
        return '';
    }
}
