<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Driver;

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

use Exception;
use In2code\In2publishCore\Command\RemoteProcedureCall\ExecuteCommand;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Communication\RemoteProcedureCall\Envelope;
use In2code\In2publishCore\Communication\RemoteProcedureCall\EnvelopeDispatcher;
use In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Utility\DatabaseUtility;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

use function array_keys;
use function is_array;
use function sprintf;

/**
 * Class RemoteFileAbstractionLayerDriver
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class RemoteFileAbstractionLayerDriver extends AbstractLimitedFilesystemDriver
{
    /** @var RemoteCommandDispatcher */
    protected $rceDispatcher;

    /** @var Letterbox */
    protected $letterBox;

    /** @var array */
    protected $remoteDriverSettings = [];

    /**
     * Maybe most important property in this class, since sending envelopes is very costly
     *
     * @var array
     */
    protected static $cache = [];

    /**
     * RemoteFileAbstractionLayerDriver constructor.
     *
     * @param array $configuration
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(array $configuration = [])
    {
        $defaultConfiguration = [
            'basePath' => '/',
            'pathType' => 'relative',
            'caseSensitive' => true,
        ];
        ArrayUtility::mergeRecursiveWithOverrule($defaultConfiguration, $configuration);
        parent::__construct($defaultConfiguration);

        $this->rceDispatcher = GeneralUtility::makeInstance(RemoteCommandDispatcher::class);
        $this->letterBox = GeneralUtility::makeInstance(Letterbox::class);
    }

    /**
     * Sets the storage uid the driver belongs to
     *
     * @param int $storageUid
     *
     * @return void
     */
    public function setStorageUid($storageUid)
    {
        $this->storageUid = (int)$storageUid;
    }

    /**
     * Initializes this object. This is called by the storage after the driver has been attached.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function initialize()
    {
        if (0 === $this->storageUid) {
            $this->remoteDriverSettings = [
                'uid' => 0,
                'pid' => 0,
                'name' => 'Fallback Storage',
                'description' => 'Internal storage, mounting the main TYPO3_site directory.',
                'driver' => 'Local',
                'processingfolder' => 'typo3temp/_processed_/',
                // legacy code
                'is_online' => true,
                'is_browsable' => true,
                'is_public' => true,
                'is_writable' => true,
                'is_default' => false,
                'configuration' => [
                    'basePath' => '/',
                    'pathType' => 'relative',
                ],
            ];
        } else {
            $query = DatabaseUtility::buildLocalDatabaseConnection()->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $this->remoteDriverSettings = $query->select('*')
                                                ->from('sys_file_storage')
                                                ->where($query->expr()->eq('uid', $this->storageUid))
                                                ->setMaxResults(1)
                                                ->execute()
                                                ->fetchAssociative();
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $driverConfiguration = $flexFormService->convertFlexFormContentToArray(
                $this->remoteDriverSettings['configuration']
            );
            ArrayUtility::mergeRecursiveWithOverrule($this->configuration, $driverConfiguration);
        }
        if (!is_array($this->remoteDriverSettings)) {
            throw new LogicException(
                'Could not find the remote storage with UID "' . $this->storageUid . '"',
                1474470724
            );
        }
    }

    /**
     * @return bool
     */
    public function isOnline(): bool
    {
        return true === (bool)$this->remoteDriverSettings['is_online'];
    }

    /**
     * @param array $identifiers
     */
    public function batchPrefetchFiles(array $identifiers)
    {
        $response = $this->executeEnvelope(
            new Envelope(
                EnvelopeDispatcher::CMD_BATCH_PREFETCH_FILES,
                ['storage' => $this->storageUid, 'identifiers' => $identifiers]
            )
        );

        foreach ($identifiers as $identifier) {
            if (isset($response[$identifier])) {
                static::$cache[$this->storageUid][$this->getFileExistsCacheIdentifier($identifier)] = true;
                static::$cache[$this->storageUid][$this->getHashCacheIdentifier($identifier, 'sha1')] =
                    $response[$identifier]['hash'];
                static::$cache[$this->storageUid][$this->getGetFileInfoByIdentifierCacheIdentifier($identifier)] =
                    $response[$identifier]['info'];
                static::$cache[$this->storageUid][$this->getGetPublicUrlCacheIdentifier($identifier)] =
                    $response[$identifier]['publicUrl'];
            } else {
                static::$cache[$this->storageUid][$this->getFileExistsCacheIdentifier($identifier)] = false;
            }
        }
    }

    public function isCaseSensitiveFileSystem(): bool
    {
        return (bool)$this->configuration['caseSensitive'];
    }

    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     *
     * @return bool
     */
    public function fileExists($fileIdentifier): bool
    {
        $callback = function () use ($fileIdentifier) {
            $response = $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_FILE_EXISTS,
                    ['storage' => $this->storageUid, 'fileIdentifier' => $fileIdentifier]
                )
            );

            if (is_array($response)) {
                $this->writeFileCaches($response);
            }

            return isset($response[$fileIdentifier]);
        };

        return $this->cache($this->getFileExistsCacheIdentifier($fileIdentifier), $callback);
    }

    /**
     * @param array $files
     */
    protected function writeFileCaches(array $files)
    {
        foreach ($files as $file => $values) {
            static::$cache[$this->storageUid][$this->getFileExistsCacheIdentifier($file)] =
                true;
            static::$cache[$this->storageUid][$this->getHashCacheIdentifier($file, 'sha1')] =
                $values['hash'];
            static::$cache[$this->storageUid][$this->getGetFileInfoByIdentifierCacheIdentifier($file)] =
                $values['info'];
            static::$cache[$this->storageUid][$this->getGetPublicUrlCacheIdentifier($file)] =
                $values['publicUrl'];
        }
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     *
     * @return bool
     * @throws Exception
     */
    public function folderExists($folderIdentifier): bool
    {
        $callback = function () use ($folderIdentifier) {
            $response = $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_FOLDER_EXISTS,
                    ['storage' => $this->storageUid, 'folderIdentifier' => $folderIdentifier]
                )
            );

            static::$cache[$this->storageUid][$this->getGetFilesInFolderCacheIdentifier($folderIdentifier)] =
                array_keys($response['files']);

            $this->writeFileCaches($response['files']);

            static::$cache[$this->storageUid][$this->getGetFoldersInFolderCacheIdentifier($folderIdentifier)] =
                $response['folders'];

            foreach ($response['folders'] as $folder) {
                static::$cache[$this->storageUid][$this->getFolderExistsCacheIdentifier($folder)] = true;
            }

            return $response['exists'];
        };

        return $this->cache($this->getFolderExistsCacheIdentifier($folderIdentifier), $callback);
    }

    /**
     * Adds a file from the local server hard disk to a given path in TYPO3s
     * virtual file system. This assumes that the local file exists, so no
     * further check is done here! After a successful the original file must
     * not exist anymore.
     *
     * @param string $localFilePath (within \TYPO3\CMS\Core\Core\Environment::getPublicPath())
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed
     *                                after successful operation
     *
     * @return string the identifier of the new file
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true): string
    {
        $callback = function () use ($localFilePath, $targetFolderIdentifier, $newFileName, $removeOriginal) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_ADD_FILE,
                    [
                        'storage' => $this->storageUid,
                        'localFilePath' => $localFilePath,
                        'targetFolderIdentifier' => $targetFolderIdentifier,
                        'newFileName' => $newFileName,
                        'removeOriginal' => $removeOriginal,
                    ]
                )
            );
        };

        return $this->cache('addFile' . $localFilePath . '|' . $targetFolderIdentifier . '|' . $newFileName, $callback);
    }

    /**
     * Renames a file in this storage.
     *
     * @param string $fileIdentifier
     * @param string $newName The target path (including the file name!)
     *
     * @return string The identifier of the file after renaming
     */
    public function renameFile($fileIdentifier, $newName): string
    {
        $callback = function () use ($fileIdentifier, $newName) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_RENAME_FILE,
                    [
                        'storage' => $this->storageUid,
                        'fileIdentifier' => $fileIdentifier,
                        'newName' => $newName,
                    ]
                )
            );
        };

        return $this->cache('renameFile' . $fileIdentifier . '|' . $newName, $callback);
    }

    /**
     * Replaces a file with file in local file system.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     *
     * @return bool TRUE if the operation succeeded
     */
    public function replaceFile($fileIdentifier, $localFilePath): bool
    {
        $callback = function () use ($fileIdentifier, $localFilePath) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_REPLACE_FILE,
                    [
                        'storage' => $this->storageUid,
                        'fileIdentifier' => $fileIdentifier,
                        'localFilePath' => $localFilePath,
                    ]
                )
            );
        };

        return $this->cache('replaceFile' . $fileIdentifier . '|' . $localFilePath, $callback);
    }

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     *
     * @return bool TRUE if deleting the file succeeded
     */
    public function deleteFile($fileIdentifier): bool
    {
        $callback = function () use ($fileIdentifier) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_DELETE_FILE,
                    ['storage' => $this->storageUid, 'fileIdentifier' => $fileIdentifier]
                )
            );
        };

        return $this->cache('deleteFile' . $fileIdentifier, $callback);
    }

    /**
     * Creates a hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     *
     * @return string
     */
    public function hash($fileIdentifier, $hashAlgorithm): string
    {
        $callback = function () use ($fileIdentifier, $hashAlgorithm) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_HASH,
                    [
                        'storage' => $this->storageUid,
                        'identifier' => $fileIdentifier,
                        'hashAlgorithm' => $hashAlgorithm,
                    ]
                )
            );
        };

        return $this->cache($this->getHashCacheIdentifier($fileIdentifier, $hashAlgorithm), $callback);
    }

    /**
     *
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
     * @param string $identifier
     *
     * @return array
     *
     * @return string
     * @throws Exception
     */
    public function getPermissions($identifier): array
    {
        $callback = function () use ($identifier) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_PERMISSIONS,
                    ['storage' => $this->storageUid, 'identifier' => $identifier]
                )
            );
        };

        return $this->cache($this->getGetPermissionsCacheIdentifier($identifier), $callback);
    }

    /**
     * Returns information about a file.
     *
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are be extracted
     *                                   If empty all will be extracted
     *
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = []): array
    {
        $callback = function () use ($fileIdentifier, $propertiesToExtract) {
            if (!$this->fileExists($fileIdentifier)) {
                throw new InvalidArgumentException('File ' . $fileIdentifier . ' does not exist.', 1476199721);
            } else {
                return $this->executeEnvelope(
                    new Envelope(
                        EnvelopeDispatcher::CMD_GET_FILE_INFO_BY_IDENTIFIER,
                        [
                            'storage' => $this->storageUid,
                            'fileIdentifier' => $fileIdentifier,
                            'propertiesToExtract' => $propertiesToExtract,
                        ]
                    )
                );
            }
        };

        return $this->cache($this->getGetFileInfoByIdentifierCacheIdentifier($fileIdentifier), $callback);
    }

    /**
     * Returns information about a file.
     *
     * @param string $folderIdentifier
     *
     * @return array
     *
     * @throws FolderDoesNotExistException
     */
    public function getFolderInfoByIdentifier($folderIdentifier): array
    {
        $callback = function () use ($folderIdentifier) {
            $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);

            if (!$this->folderExists($folderIdentifier)) {
                throw new FolderDoesNotExistException(
                    'Folder "' . $folderIdentifier . '" does not exist.',
                    1314516810
                );
            }
            return [
                'identifier' => $folderIdentifier,
                'name' => PathUtility::basename($folderIdentifier),
                'storage' => $this->storageUid,
            ];
        };

        return $this->cache($this->getGetFolderInfoByIdentifierCacheIdentifier($folderIdentifier), $callback);
    }

    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     *
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $filenameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ): array {
        if (
            0 !== $start
            || 0 !== $numberOfItems
            || false !== $recursive
            || !empty($filenameFilterCallbacks)
            || '' !== $sort
            || false !== $sortRev
        ) {
            throw new InvalidArgumentException('This Driver does not support optional arguments', 1476202118);
        }

        $callback = function () use ($folderIdentifier) {
            if (!$this->folderExists($folderIdentifier)) {
                throw new InvalidArgumentException(
                    'Cannot list items in directory ' . $folderIdentifier . ' - does not exist or is no directory',
                    1475235331
                );
            }

            $files = $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_FILES_IN_FOLDER,
                    ['storage' => $this->storageUid, 'folderIdentifier' => $folderIdentifier]
                )
            );

            $this->writeFileCaches($files);

            return array_keys($files);
        };

        return $this->cache($this->getGetFilesInFolderCacheIdentifier($folderIdentifier), $callback);
    }

    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     *
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $folderNameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ): array {
        if (
            0 !== $start
            || 0 !== $numberOfItems
            || false !== $recursive
            || !empty($folderNameFilterCallbacks)
            || '' !== $sort
            || false !== $sortRev
        ) {
            throw new InvalidArgumentException('This Driver does not support optional arguments', 1476201945);
        }

        $callback = function () use ($folderIdentifier) {
            $folders = $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_FOLDERS_IN_FOLDER,
                    ['storage' => $this->storageUid, 'folderIdentifier' => $folderIdentifier]
                )
            );

            foreach ($folders as $folder) {
                static::$cache[$this->storageUid][$this->getFolderExistsCacheIdentifier($folder)] = true;
            }

            return $folders;
        };

        return $this->cache($this->getGetFoldersInFolderCacheIdentifier($folderIdentifier), $callback);
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to \TYPO3\CMS\Core\Core\Environment::getPublicPath() (rawurlencoded).
     *
     * @param string $identifier
     *
     * @return string
     */
    public function getPublicUrl($identifier): string
    {
        $callback = function () use ($identifier) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_PUBLIC_URL,
                    ['storage' => $this->storageUid, 'identifier' => $identifier]
                )
            );
        };

        return $this->cache($this->getGetPublicUrlCacheIdentifier($identifier), $callback);
    }

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param bool $recursive
     *
     * @return string the Identifier of the new folder
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false): string
    {
        $callback = function () use ($newFolderName, $parentFolderIdentifier, $recursive) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_CREATE_FOLDER,
                    [
                        'storage' => $this->storageUid,
                        '$newFolderName' => $newFolderName,
                        '$parentFolderIdentifier' => $parentFolderIdentifier,
                        '$recursive' => $recursive,
                    ]
                )
            );
        };

        return $this->cache('createFolder|' . $newFolderName . $parentFolderIdentifier, $callback);
    }

    /**
     * Removes a folder in filesystem.
     *
     * @param string $folderIdentifier
     * @param bool $deleteRecursively
     *
     * @return bool
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false): bool
    {
        $callback = function () use ($folderIdentifier, $deleteRecursively) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_DELETE_FOLDER,
                    [
                        'storage' => $this->storageUid,
                        'folderIdentifier' => $folderIdentifier,
                        'deleteRecursively' => $deleteRecursively,
                    ]
                )
            );
        };

        return $this->cache('createFolder|' . $folderIdentifier, $callback);
    }

    /**
     * This method is not cached!
     *
     * Moves a file *within* the current storage.
     * Note that this is only about an inner-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName): string
    {
        return $this->executeEnvelope(
            new Envelope(
                EnvelopeDispatcher::CMD_MOVE_FILE_WITHIN_STORAGE,
                [
                    'storage' => $this->storageUid,
                    'fileIdentifier' => $fileIdentifier,
                    'targetFolderIdentifier' => $targetFolderIdentifier,
                    'newFileName' => $newFileName,
                ]
            )
        );
    }

    /**
     * @param Envelope $envelope
     *
     * @return mixed
     *
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function executeEnvelope(Envelope $envelope)
    {
        $uid = $this->letterBox->sendEnvelope($envelope);

        if (false === $uid) {
            throw new In2publishCoreException(
                'Could not send ' . $envelope->getCommand() . ' request to remote system',
                1476296011
            );
        }

        $request = new RemoteCommandRequest(ExecuteCommand::IDENTIFIER, [], [$uid]);
        $response = $this->rceDispatcher->dispatch($request);

        if (!$response->isSuccessful()) {
            throw new RuntimeException(
                sprintf(
                    'Could not execute RPC [%d]. Errors and Output: %s %s',
                    $uid,
                    $response->getErrorsString(),
                    $response->getOutputString()
                ),
                1476281965
            );
        }

        $envelope = $this->letterBox->receiveEnvelope($uid);

        if (false === $envelope) {
            throw new In2publishCoreException('Could not receive envelope [' . $uid . ']', 1486727017);
        }
        return $envelope->getResponse();
    }

    /**
     * Callback cache proxy method. If the identifier's cache entry is not found it is generated by invoking the
     * callback and stored afterwards
     *
     * @param string $identifier
     * @param callable $callback
     *
     * @return mixed
     */
    protected function cache(string $identifier, callable $callback)
    {
        if (!isset(static::$cache[$this->storageUid][$identifier])) {
            static::$cache[$this->storageUid][$identifier] = $callback();
        }

        return static::$cache[$this->storageUid][$identifier];
    }

    /**
     * @param string $folderIdentifier
     *
     * @return string
     */
    protected function getFolderExistsCacheIdentifier(string $folderIdentifier): string
    {
        return 'folderExists|' . $folderIdentifier;
    }

    /**
     * @param string $folderIdentifier
     *
     * @return string
     */
    protected function getGetFoldersInFolderCacheIdentifier(string $folderIdentifier): string
    {
        return 'getFoldersInFolder|' . $folderIdentifier;
    }

    /**
     * @param string $folderIdentifier
     *
     * @return string
     */
    protected function getGetFilesInFolderCacheIdentifier(string $folderIdentifier): string
    {
        return 'getFilesInFolder|' . $folderIdentifier;
    }

    /**
     * @param string $fileIdentifier
     *
     * @return string
     */
    protected function getFileExistsCacheIdentifier(string $fileIdentifier): string
    {
        return 'fileExists|' . $fileIdentifier;
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    protected function getGetPermissionsCacheIdentifier(string $identifier): string
    {
        return 'getPermissions|' . $identifier;
    }

    /**
     * @param string $fileIdentifier
     *
     * @return string
     */
    protected function getGetFileInfoByIdentifierCacheIdentifier(string $fileIdentifier): string
    {
        return 'getFileInfoByIdentifier|' . $fileIdentifier;
    }

    /**
     * @param string $folderIdentifier
     *
     * @return string
     */
    protected function getGetFolderInfoByIdentifierCacheIdentifier(string $folderIdentifier): string
    {
        return 'getFolderInfoByIdentifier|' . $folderIdentifier;
    }

    /**
     * @param string $fileIdentifier
     * @param string $hashAlgorithm
     *
     * @return string
     */
    protected function getHashCacheIdentifier(string $fileIdentifier, string $hashAlgorithm): string
    {
        return 'hash|' . $fileIdentifier . '|' . $hashAlgorithm;
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    protected function getGetPublicUrlCacheIdentifier(string $identifier): string
    {
        return 'getPublicUrl|' . $identifier;
    }

    /**
     * Resets the internal cache
     */
    public function clearCache()
    {
        static::$cache[$this->storageUid] = [];
    }
}
