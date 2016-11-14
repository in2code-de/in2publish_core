<?php
namespace In2code\In2publishCore\Domain\Driver;

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

use In2code\In2publishCore\Domain\Driver\Rpc\Envelope;
use In2code\In2publishCore\Domain\Driver\Rpc\EnvelopeDispatcher;
use In2code\In2publishCore\Domain\Driver\Rpc\Letterbox;
use In2code\In2publishCore\Security\SshConnection;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class RemoteFileAbstractionLayerDriver
 */
class RemoteFileAbstractionLayerDriver extends AbstractHierarchicalFilesystemDriver implements
    DriverInterface,
    SingletonInterface
{
    /**
     * @var SshConnection
     */
    protected $sshConnection = null;

    /**
     * @var Letterbox
     */
    protected $letterBox = null;

    /**
     * @var array
     */
    protected $remoteDriverSettings = array();

    /**
     * Maybe most important property in this class, since sending envelopes is very costly
     *
     * @var array
     */
    protected $cache = array();

    /**
     * RemoteFileAbstractionLayerDriver constructor.
     * @param array $configuration
     */
    public function __construct(array $configuration = array())
    {
        parent::__construct($configuration);
        $this->sshConnection = SshConnection::makeInstance();
        $this->letterBox = GeneralUtility::makeInstance('In2code\\In2publishCore\\Domain\\Driver\\Rpc\\Letterbox');
    }

    /**
     * Sets the storage uid the driver belongs to
     *
     * @param int $storageUid
     * @return void
     */
    public function setStorageUid($storageUid)
    {
        $this->storageUid = $storageUid;
    }

    /**
     * Initializes this object. This is called by the storage after the driver has been attached.
     *
     * @return void
     */
    public function initialize()
    {
        if (0 === (int)$this->storageUid) {
            $this->remoteDriverSettings = array(
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
                'configuration' => array(
                    'basePath' => '/',
                    'pathType' => 'relative',
                ),
            );
            $this->configuration = array(
                'basePath' => '/',
                'pathType' => 'relative',
            );
        } else {
            $this->remoteDriverSettings = DatabaseUtility::buildForeignDatabaseConnection()->exec_SELECTgetSingleRow(
                '*',
                'sys_file_storage',
                'uid=' . (int)$this->storageUid
            );
            $flexFormService = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\FlexFormService');
            $this->configuration = $flexFormService->convertFlexFormContentToArray(
                $this->remoteDriverSettings['configuration']
            );
        }
        if (!is_array($this->remoteDriverSettings)) {
            throw new \LogicException('Could not find the remote storage.', 1474470724);
        }
    }

    /**
     * @return bool
     */
    public function isOnline()
    {
        return true === (bool)$this->remoteDriverSettings['is_online'];
    }

    /**
     * @return bool
     */
    public function isCaseSensitiveFileSystem()
    {
        return $this->configuration['caseSensitive'];
    }

    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     * @return bool
     */
    public function fileExists($fileIdentifier)
    {
        $callback = function () use ($fileIdentifier) {
            $response = $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_FILE_EXISTS,
                    array('storage' => $this->storageUid, 'fileIdentifier' => $fileIdentifier)
                )
            );

            if (is_array($response)) {
                foreach ($response as $file => $values) {
                    $this->cache[$this->storageUid][$this->getFileExistsCacheIdentifier($file)] = true;
                    $this->cache[$this->storageUid][$this->getHashCacheIdentifier($file, 'sha1')] = $values['hash'];
                    $this->cache[$this->storageUid][$this->getGetFileInfoByIdentifierCacheIdentifier($file)] =
                        $values['info'];
                    $this->cache[$this->storageUid][$this->getGetPublicUrlCacheIdentifier($file)] =
                        $values['publicUrl'];
                }
            }

            return isset($response[$fileIdentifier]);
        };

        return $this->cache($this->getFileExistsCacheIdentifier($fileIdentifier), $callback);
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     * @throws \Exception
     * @return bool
     */
    public function folderExists($folderIdentifier)
    {
        $callback = function () use ($folderIdentifier) {
            $response = $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_FOLDER_EXISTS,
                    array('storage' => $this->storageUid, 'folderIdentifier' => $folderIdentifier)
                )
            );

            $this->cache[$this->storageUid][$this->getGetFilesInFolderCacheIdentifier($folderIdentifier)] = array_keys(
                $response['files']
            );

            foreach ($response['files'] as $file => $values) {
                $this->cache[$this->storageUid][$this->getFileExistsCacheIdentifier($file)] = true;
                $this->cache[$this->storageUid][$this->getHashCacheIdentifier($file, 'sha1')] = $values['hash'];
                $this->cache[$this->storageUid][$this->getGetFileInfoByIdentifierCacheIdentifier($file)] =
                    $values['info'];
                $this->cache[$this->storageUid][$this->getGetPublicUrlCacheIdentifier($file)] = $values['publicUrl'];
            }

            $this->cache[$this->storageUid][$this->getGetFoldersInFolderCacheIdentifier($folderIdentifier)] =
                $response['folders'];

            foreach ($response['folders'] as $folder) {
                $this->cache[$this->storageUid][$this->getFolderExistsCacheIdentifier($folder)] = true;
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
     * @param string $localFilePath (within PATH_site)
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed
     *                                after successful operation
     * @return string the identifier of the new file
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        $callback = function () use ($localFilePath, $targetFolderIdentifier, $newFileName, $removeOriginal) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_ADD_FILE,
                    array(
                        'storage' => $this->storageUid,
                        'localFilePath' => $localFilePath,
                        'targetFolderIdentifier' => $targetFolderIdentifier,
                        'newFileName' => $newFileName,
                        'removeOriginal' => $removeOriginal,
                    )
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
     * @return string The identifier of the file after renaming
     */
    public function renameFile($fileIdentifier, $newName)
    {
        $callback = function () use ($fileIdentifier, $newName) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_RENAME_FILE,
                    array(
                        'storage' => $this->storageUid,
                        'fileIdentifier' => $fileIdentifier,
                        'newName' => $newName,
                    )
                )
            );
        };

        return $this->cache(__FUNCTION__ . $fileIdentifier . '|' . $newName, $callback);
    }

    /**
     * Replaces a file with file in local file system.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return bool TRUE if the operation succeeded
     */
    public function replaceFile($fileIdentifier, $localFilePath)
    {
        $callback = function () use ($fileIdentifier, $localFilePath) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_REPLACE_FILE,
                    array(
                        'storage' => $this->storageUid,
                        'fileIdentifier' => $fileIdentifier,
                        'localFilePath' => $localFilePath,
                    )
                )
            );
        };

        return $this->cache(__FUNCTION__ . $fileIdentifier . '|' . $localFilePath, $callback);
    }

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     * @return bool TRUE if deleting the file succeeded
     */
    public function deleteFile($fileIdentifier)
    {
        $callback = function () use ($fileIdentifier) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_DELETE_FILE,
                    array('storage' => $this->storageUid, 'fileIdentifier' => $fileIdentifier)
                )
            );
        };

        return $this->cache(__FUNCTION__ . $fileIdentifier, $callback);
    }

    /**
     * Creates a hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     * @return string
     */
    public function hash($fileIdentifier, $hashAlgorithm)
    {
        $callback = function () use ($fileIdentifier, $hashAlgorithm) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_HASH,
                    array(
                        'storage' => $this->storageUid,
                        'identifier' => $fileIdentifier,
                        'hashAlgorithm' => $hashAlgorithm,
                    )
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
     * @return array
     *
     * @return string
     * @throws \Exception
     */
    public function getPermissions($identifier)
    {
        $callback = function () use ($identifier) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_PERMISSIONS,
                    array('storage' => $this->storageUid, 'identifier' => $identifier)
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
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = array())
    {
        $callback = function () use ($fileIdentifier, $propertiesToExtract) {
            if (!$this->fileExists($fileIdentifier)) {
                throw new \InvalidArgumentException('File ' . $fileIdentifier . ' does not exist.', 1476199721);
            } else {
                return $this->executeEnvelope(
                    new Envelope(
                        EnvelopeDispatcher::CMD_GET_FILE_INFO_BY_IDENTIFIER,
                        array(
                            'storage' => $this->storageUid,
                            'fileIdentifier' => $fileIdentifier,
                            'propertiesToExtract' => $propertiesToExtract,
                        )
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
     * @return array
     *
     * @throws FolderDoesNotExistException
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        $callback = function () use ($folderIdentifier) {
            $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);

            if (!$this->folderExists($folderIdentifier)) {
                throw new FolderDoesNotExistException(
                    'Folder "' . $folderIdentifier . '" does not exist.',
                    1314516810
                );
            }
            return array(
                'identifier' => $folderIdentifier,
                'name' => PathUtility::basename($folderIdentifier),
                'storage' => $this->storageUid,
            );
        };

        return $this->cache($this->getGetFolderInfoByIdentifierCacheIdentifier($folderIdentifier), $callback);
    }

    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $max
     * @param bool $recursive
     * @param array $fnFc callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $max = 0,
        $recursive = false,
        array $fnFc = array(),
        $sort = '',
        $sortRev = false
    ) {
        if (0 !== $start || 0 !== $max || false !== $recursive || !empty($fnFc) || '' !== $sort || false !== $sortRev) {
            throw new \InvalidArgumentException('This Driver does not support optional arguments', 1476202118);
        }
        $callback = function () use ($folderIdentifier) {
            if (!$this->folderExists($folderIdentifier)) {
                throw new \InvalidArgumentException(
                    'Cannot list items in directory ' . $folderIdentifier . ' - does not exist or is no directory',
                    1475235331
                );
            }

            $files = $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_FILES_IN_FOLDER,
                    array('storage' => $this->storageUid, 'folderIdentifier' => $folderIdentifier)
                )
            );

            foreach ($files as $file => $values) {
                $this->cache[$this->storageUid][$this->getFileExistsCacheIdentifier($file)] = true;
                $this->cache[$this->storageUid][$this->getHashCacheIdentifier($file, 'sha1')] = $values['hash'];
                $this->cache[$this->storageUid][$this->getGetFileInfoByIdentifierCacheIdentifier($file)] =
                    $values['info'];
                $this->cache[$this->storageUid][$this->getGetPublicUrlCacheIdentifier($file)] = $values['publicUrl'];
            }

            return array_keys($files);
        };

        return $this->cache($this->getGetFilesInFolderCacheIdentifier($folderIdentifier), $callback);
    }

    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $max
     * @param bool $recursive
     * @param array $fnFc callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $max = 0,
        $recursive = false,
        array $fnFc = array(),
        $sort = '',
        $sortRev = false
    ) {
        if (0 !== $start || 0 !== $max || false !== $recursive || !empty($fnFc) || '' !== $sort || false !== $sortRev) {
            throw new \InvalidArgumentException('This Driver does not support optional arguments', 1476201945);
        }

        $callback = function () use ($folderIdentifier) {
            $folders = $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_FOLDERS_IN_FOLDER,
                    array('storage' => $this->storageUid, 'folderIdentifier' => $folderIdentifier)
                )
            );

            foreach ($folders as $folder) {
                $this->cache[$this->storageUid][$this->getFolderExistsCacheIdentifier($folder)] = true;
            }

            return $folders;
        };

        return $this->cache($this->getGetFoldersInFolderCacheIdentifier($folderIdentifier), $callback);
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to PATH_site (rawurlencoded).
     *
     * @param string $identifier
     * @return string
     */
    public function getPublicUrl($identifier)
    {
        $callback = function () use ($identifier) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_GET_PUBLIC_URL,
                    array('storage' => $this->storageUid, 'identifier' => $identifier)
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
     * @return string the Identifier of the new folder
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false)
    {
        $callback = function () use ($newFolderName, $parentFolderIdentifier, $recursive) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_CREATE_FOLDER,
                    array(
                        'storage' => $this->storageUid,
                        '$newFolderName' => $newFolderName,
                        '$parentFolderIdentifier' => $parentFolderIdentifier,
                        '$recursive' => $recursive,
                    )
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
     * @return bool
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        $callback = function () use ($folderIdentifier, $deleteRecursively) {
            return $this->executeEnvelope(
                new Envelope(
                    EnvelopeDispatcher::CMD_DELETE_FOLDER,
                    array(
                        'storage' => $this->storageUid,
                        'folderIdentifier' => $folderIdentifier,
                        'deleteRecursively' => $deleteRecursively,
                    )
                )
            );
        };

        return $this->cache('createFolder|' . $folderIdentifier, $callback);
    }

    /**
     * @param Envelope $envelope
     * @return mixed
     * @throws \Exception
     */
    protected function executeEnvelope(Envelope $envelope)
    {
        $uid = $this->letterBox->sendEnvelope($envelope);

        if (false === $uid) {
            throw new \Exception('Could not send ' . $envelope->getCommand() . ' request to remote system', 1476296011);
        }

        $executionResult = $this->sshConnection->executeRpc($uid);

        if (!empty($executionResult)) {
            throw new \RuntimeException(
                'Could not execute RPC. An error occurred on foreign: ' . implode(',', $executionResult),
                1476281965
            );
        }

        return $this->letterBox->receiveEnvelope($uid)->getResponse();
    }

    /**
     * Callback cache proxy method. If the identifier's cache entry is not found it is generated by invoking the
     * callback and stored afterwards
     *
     * @param string $identifier
     * @param callable $callback
     * @return mixed
     */
    protected function cache($identifier, $callback)
    {
        if (!isset($this->cache[$this->storageUid][$identifier])) {
            $this->cache[$this->storageUid][$identifier] = $callback();
        }

        return $this->cache[$this->storageUid][$identifier];
    }

    /**
     * @param string $folderIdentifier
     * @return string
     */
    protected function getFolderExistsCacheIdentifier($folderIdentifier)
    {
        return 'folderExists|' . $folderIdentifier;
    }

    /**
     * @param string $folderIdentifier
     * @return string
     */
    protected function getGetFoldersInFolderCacheIdentifier($folderIdentifier)
    {
        return 'getFoldersInFolder|' . $folderIdentifier;
    }

    /**
     * @param string $folderIdentifier
     * @return string
     */
    protected function getGetFilesInFolderCacheIdentifier($folderIdentifier)
    {
        return 'getFilesInFolder|' . $folderIdentifier;
    }

    /**
     * @param string $fileIdentifier
     * @return string
     */
    protected function getFileExistsCacheIdentifier($fileIdentifier)
    {
        return 'fileExists|' . $fileIdentifier;
    }

    /**
     * @param string $identifier
     * @return string
     */
    protected function getGetPermissionsCacheIdentifier($identifier)
    {
        return 'getPermissions|' . $identifier;
    }

    /**
     * @param string $fileIdentifier
     * @return string
     */
    protected function getGetFileInfoByIdentifierCacheIdentifier($fileIdentifier)
    {
        return 'getFileInfoByIdentifier|' . $fileIdentifier;
    }

    /**
     * @param string $folderIdentifier
     * @return string
     */
    protected function getGetFolderInfoByIdentifierCacheIdentifier($folderIdentifier)
    {
        return 'getFolderInfoByIdentifier|' . $folderIdentifier;
    }

    /**
     * @param string $fileIdentifier
     * @param string $hashAlgorithm
     * @return string
     */
    protected function getHashCacheIdentifier($fileIdentifier, $hashAlgorithm)
    {
        return 'hash|' . $fileIdentifier . '|' . $hashAlgorithm;
    }

    /**
     * @param string $identifier
     * @return string
     */
    protected function getGetPublicUrlCacheIdentifier($identifier)
    {
        return 'getPublicUrl|' . $identifier;
    }

    /**
     * Resets the internal cache
     */
    public function clearCache()
    {
        $this->cache[$this->storageUid] = array();
    }

    /****************************************************************
     *
     *              NOT IMPLEMENTED; NOT NEEDED
     *
     ****************************************************************/

    /**
     * Not required
     *
     * @return string
     */
    public function getDefaultFolder()
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201277);
    }

    /**
     * Renames a folder in this storage.
     *
     * @param string $folderIdentifier
     * @param string $newName
     * @return array A map of old to new file identifiers of all affected resources
     */
    public function renameFolder($folderIdentifier, $newName)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201295);
    }

    /**
     * Returns the number of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @return int Number of files in folder
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = array())
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201312);
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $fnFc callbacks for filtering the items
     * @return int Number of folders in folder
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $fnFc = array())
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201325);
    }

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     * @return string folder identifier
     */
    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201370);
    }

    /**
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string file identifier
     */
    public function getFileInFolder($fileName, $folderIdentifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201421);
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     * @return void
     */
    public function dumpFileContents($identifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201434);
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201437);
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     */
    public function getFileContents($fileIdentifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201459);
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return int The number of bytes written to the file
     */
    public function setFileContents($fileIdentifier, $contents)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201462);
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201464);
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201467);
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return string The path to the file on the local disk
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201472);
    }

    /**
     * Moves a file *within* the current storage.
     * Note that this is only about an inner-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @return string
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201491);
    }

    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return array All files which are affected, map of old => new file identifiers
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201494);
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return bool
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201496);
    }

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param string $fileName
     * @param string $parentFolderIdentifier
     * @return string
     */
    public function createFile($fileName, $parentFolderIdentifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201512);
    }

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $fileName
     * @return string the Identifier of the new file
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201516);
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201527);
    }

    /**
     * Not required
     *
     * @param int $capabilities
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201544);
    }

    /**
     * Never called
     *
     * @return void
     */
    public function processConfiguration()
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201555);
    }

    /**
     * Not required
     *
     * @param int $capability
     * @return bool
     */
    public function hasCapability($capability)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201628);
    }

    /**
     * Not required
     *
     * @return string
     */
    public function getRootLevelFolder()
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201635);
    }
}
