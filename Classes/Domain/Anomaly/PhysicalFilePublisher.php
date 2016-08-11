<?php
namespace In2code\In2publishCore\Domain\Anomaly;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Security\SshConnection;
use In2code\In2publishCore\Utility\DatabaseUtility;
use In2code\In2publishCore\Utility\FileUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;

/**
 * Class PhysicalFilePublisher
 */
class PhysicalFilePublisher implements SingletonInterface
{
    const LOCAL = 'local';
    const FOREIGN = 'foreign';

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var SshConnection
     */
    protected $sshConnection = null;

    /**
     * @var FlexFormService
     */
    protected $flexFormService = null;

    /**
     * @var array
     */
    protected $storages = array(
        self::LOCAL => array(),
        self::FOREIGN => array(),
    );

    /**
     * @var array
     */
    protected $cache = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
        $this->sshConnection = SshConnection::makeInstance();
        $this->flexFormService = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\FlexFormService');

        // set local and foreign storage records
        $this->storages[self::LOCAL] = $this->fetchStorageRows(DatabaseUtility::buildLocalDatabaseConnection());
        $this->storages[self::FOREIGN] = $this->fetchStorageRows(DatabaseUtility::buildForeignDatabaseConnection());
    }

    /**
     * Check both files and apply the appropriate action:
     *  * Delete the remote file
     *  * Publish the local file
     *  * Update the remote file
     *  * Rename the remote file
     *
     * @param string $tableName
     * @param Record $record
     * @return null Returns always null, because slot return values (arrays) are remapped and booleans are not allowed
     * @throws \Exception
     */
    public function publishPhysicalFileOfSysFile($tableName, Record $record)
    {
        if ($this->isSupportedTable($tableName)) {
            $cacheIdentifier = md5($record->getMergedProperty('storage') . $record->getMergedProperty('identifier'));

            // We cache the result of previous file publishing, because it
            // is very common that a resource is referenced multiple times
            if (isset($this->cache[$tableName][$cacheIdentifier])) {
                return null;
            }

            $fileInfo = $this->getFileInfo($record);

            // in case the file info equals false we were not able to read
            // the required information from the rows and we can't publish
            if (false === $fileInfo) {
                $this->cache[$tableName][$cacheIdentifier] = false;
                return null;
            }

            // If we've got at least one file info we can publish the file
            list($foreignFileInfo, $localFileInfo) = $fileInfo;

            $result = null;

            // At this point we are certain, that at least one file exists
            // and we have already fetched the required information around
            // the file's identifier with the early retrieved FAL Storages

            // No we decide based on the record state which action we take

            // In case the record representing the file on the file system
            // has been deleted the related file was also deleted by TYPO3
            // To avoid obsolete files on the remote system we delete them
            // as well (the foreign record pointing to the foreign file is
            // deleted, so we remove the physical file on foreign as well)
            if (RecordInterface::RECORD_STATE_DELETED === $record->getState()) {
                $result = $this->removeFile($foreignFileInfo);
            } else {
                // Otherwise the file gains some special treatment through
                // a logic that keep's the remote physical file up to date

                // If the foreign file hash does not exist, the file needs
                // to be published. Write it to the target remote location
                if (empty($foreignFileInfo['hash'])) {
                    $result = $this->publishFile($localFileInfo);
                } else {
                    // Assuming the physical file exists on both local and
                    // foreign it requires either to be updated or renamed
                    if ($localFileInfo['hash'] === $foreignFileInfo['hash']) {
                        // The contents are identical, no update is needed
                        if ($localFileInfo['relativePath'] === $foreignFileInfo['relativePath']) {
                            // The path and name is identical, too. We can
                            // return at this point, no action is required
                            $result = 'no-changes';
                        } else {
                            // Rename the remote file to the new file name
                            $result = $this->renameFile(
                                $foreignFileInfo['relativePath'],
                                $localFileInfo['relativePath']
                            );
                        }
                    } else {
                        // The contents do not match but the file names do
                        // therefore simply publish the file to transcribe
                        if ($localFileInfo['relativePath'] === $foreignFileInfo['relativePath']) {
                            $result = $this->publishFile($localFileInfo);
                        } else {
                            // The files are completely different. Neither
                            // file contents nor identifiers are equal. We
                            // will not take any action because this in an
                            // edge case that will likely not arise. Abort
                            $result = 'too-different';
                        }
                    }
                }
            }
            if (false === $result) {
                $this->logger->error('Notice: Publishing file failed. See previous message');
            }
            $this->cache[$tableName][$cacheIdentifier] = $result;
        }
        return null;
    }

    /**
     * @param string $oldIdentifier
     * @param string $newIdentifier
     * @return bool
     */
    protected function renameFile($oldIdentifier, $newIdentifier)
    {
        $targetedRemoteFolder = dirname($newIdentifier);
        $logData = array(
            'oldIdentifier' => $oldIdentifier,
            'newIdentifier' => $newIdentifier,
            'targetedRemoteFolder' => $targetedRemoteFolder,
        );
        // Ensure the existence of all parent folders on the remote system
        // because the file might not just get renamed but also moved into
        // another newly created folder. We know it works but still log it
        if ($this->sshConnection->createFolderByIdentifierOnRemote($targetedRemoteFolder)) {
            if ($this->sshConnection->renameRemoteFile($oldIdentifier, $newIdentifier)) {
                $this->logger->notice('Renamed remote file', array($logData));
                return true;
            } else {
                $this->logger->error('Failed to rename remote file', array($logData));
            }
        } else {
            $this->logger->error('Failed to create targeted remote folder', array($logData));
        }
        return false;
    }

    /**
     * @param array $foreignFileInfo
     * @return bool
     */
    protected function removeFile(array $foreignFileInfo)
    {
        // The hash is exclusively set if the actual file exists on remote
        if (empty($foreignFileInfo['hash'])) {
            $this->logger->error('Tried to delete a non existent foreign file', array('fileInfo' => $foreignFileInfo));
        } else {
            if ($this->sshConnection->removeRemoteFile($foreignFileInfo['relativePath'])) {
                $this->logger->warning('Deleted physical file on foreign', array('fileInfo' => $foreignFileInfo));
                return true;
            } else {
                $this->logger->error('Failed to delete foreign physical file', array('fileInfo' => $foreignFileInfo));
            }
        }
        return false;
    }

    /**
     * @param string $localFileInfo
     * @return bool
     * @throws \Exception
     */
    protected function publishFile($localFileInfo)
    {
        $localFalFile = ResourceFactory::getInstance()->getFileObjectByStorageAndIdentifier(
            $localFileInfo['storage'],
            $localFileInfo['identifier']
        );
        if ($this->sshConnection->sendFile($localFalFile)) {
            $this->logger->notice('Published file', array('fileInfo' => $localFileInfo));
            return true;
        } else {
            $this->logger->error('Failed publishing a file', array('fileInfo' => $localFileInfo));
            return false;
        }
    }

    /**
     * @param string $side
     * @param Record $record
     * @param array $fileInformation
     * @return array
     */
    protected function addFileInformationByRecord($side, Record $record, array $fileInformation)
    {
        if (self::LOCAL === $side) {
            $fileInformation['storage'] = (int)$record->getLocalProperty('storage');
            $fileInformation['identifier'] = $record->getLocalProperty('identifier');
        } elseif (self::FOREIGN === $side) {
            $fileInformation['storage'] = (int)$record->getForeignProperty('storage');
            $fileInformation['identifier'] = $record->getForeignProperty('identifier');
        }
        return $fileInformation;
    }

    /**
     * @param string $side
     * @param Record $record
     * @return bool
     */
    protected function gatherFileInformation($side, Record $record)
    {
        $fileInformation = array(
            'relativePath' => '',
            'hash' => '',
            'storage' => '',
            'identifier' => '',
        );
        $fileInformation = $this->addFileInformationByRecord($side, $record, $fileInformation);
        $storages = $this->storages[$side];
        $identifier = $fileInformation['identifier'];

        // Fallback mode: The storage is zero, which means that the record
        // reference to the file isn't FAL enabled (mostly TCA type group)
        if (0 === $fileInformation['storage']) {
            if (!$this->isLegacyResource($identifier)) {
                // If there is no configured storage and the file does not
                // represent a legacy resource and is neither a static one
                // we can not resolve the correct file's relative location
                $this->logUnsupportedRelationAndExit($side, $identifier);
            } else {
                // This is definitely TCA type group or an image thumbnail
                // Assumed this, the identifier reflects the relative path
                // and we don't have to combine it with the storage's root
                $fileInformation = $this->addFileInformationByRelativeIdentifier($side, $identifier, $fileInformation);
            }
        } else {
            // If a sys_file_storage exists for the given storage uid from
            // the supported file information row, we can extract the base
            // path of that storage, given it's powered by the LocalDriver
            if (!$this->hasConfiguredStorage($fileInformation, $storages)) {
                // Log and skip the sys_file_storage can not be determined
                $this->logMissingStorageAndExit($fileInformation);
            } else {
                $storage = $storages[$fileInformation['storage']];
                if (!$this->isSupportedStorage($storage)) {
                    // Log and skip this file if the Driver is unsupported
                    $this->logUnsupportedStorageAndExit($fileInformation);
                } else {
                    // Convert the storage's FlexForm configuration values
                    // to an array to extract the base path. The base path
                    // will be prepend to the file's identifier to get the
                    // complete path relative to the TYPO3's document root
                    $basePath = $this->getStorageBasePath($storage);
                    $fileInformation = $this->addFileInformationByRelativeIdentifier(
                        $side,
                        $basePath . $identifier,
                        $fileInformation
                    );
                }
            }
        }
        return $fileInformation;
    }

    /**
     * @param array $storage
     * @return string
     */
    protected function getStorageBasePath(array $storage)
    {
        $configuration = $this->getStorageConfiguration($storage);
        return $configuration['basePath'];
    }

    /**
     * Retrieve a storage's configuration as array with internal caching
     *
     * @param array $storage The sys_file_storage_row
     * @return array
     */
    protected function getStorageConfiguration(array $storage)
    {
        $cacheIdentifier = md5($storage['configuration']);
        if (!array_key_exists($cacheIdentifier, $this->cache)) {
            $this->cache[$cacheIdentifier] = $this->flexFormService->convertFlexFormContentToArray(
                $storage['configuration']
            );
        }
        return $this->cache[$cacheIdentifier];
    }

    /**
     * @param string $side
     * @param string $relativeIdentifier
     * @return bool|string
     */
    protected function getFileHash($side, $relativeIdentifier)
    {
        if (self::LOCAL === $side && FileUtility::fileExists($relativeIdentifier)) {
            return FileUtility::hash($relativeIdentifier);
        } elseif (self::FOREIGN === $side && $this->sshConnection->isRemoteFileExisting($relativeIdentifier)) {
            return $this->sshConnection->remoteFileHash($relativeIdentifier);
        }
        return false;
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @return array
     */
    protected function fetchStorageRows(DatabaseConnection $databaseConnection)
    {
        return (array)$databaseConnection->exec_SELECTgetRows('*', 'sys_file_storage', '1=1', '', '', '', 'uid');
    }

    /**
     * @param string $identifier Relative file identifier (taken from sys_file).
     * @return bool Returns true if the identifier represents a static (non-publishable) resource
     */
    protected function containsStaticResource($identifier)
    {
        return 0 === strpos($identifier, '/typo3conf') || 0 === strpos($identifier, '/sysext');
    }

    /**
     * @param string $tableName
     * @return bool
     */
    protected function isSupportedTable($tableName)
    {
        return in_array($tableName, array('sys_file_processedfile', 'sys_file'));
    }

    /**
     * Also treat fileadmin resources as legacy resources (depends on previous storage === 0 check)
     *
     * @param string $identifier
     * @return bool
     */
    protected function isLegacyResource($identifier)
    {
        return 0 === strpos($identifier, '/uploads/')
               || 0 === strpos($identifier, '/typo3temp/')
               || 0 === strpos($identifier, '/fileadmin/');
    }

    /**
     * @param $fileInformation
     * @param $storages
     * @return bool
     */
    protected function hasConfiguredStorage($fileInformation, $storages)
    {
        return array_key_exists($fileInformation['storage'], $storages);
    }

    /**
     * @param $storage
     * @return bool
     */
    protected function isSupportedStorage($storage)
    {
        return 'Local' === $storage['driver'];
    }

    /**
     * @param $side
     * @param $relativeIdentifier
     * @param $fileInformation
     * @return mixed
     */
    protected function addFileInformationByRelativeIdentifier($side, $relativeIdentifier, $fileInformation)
    {
        $fileInformation['relativePath'] = $relativeIdentifier;
        $fileInformation['hash'] = $this->getFileHash($side, $relativeIdentifier);
        return $fileInformation;
    }

    /**
     * @param $fileInformation
     */
    protected function logUnsupportedStorageAndExit($fileInformation)
    {
        $this->logger->error('Only LocalDriver is supported for publishing!', array('fileInfo' => $fileInformation));
        throw new \RuntimeException(
            'File [' . $fileInformation['identifier'] . '] uses a non LocalDriver enabled Storage ['
            . $fileInformation['storage'] . ']!',
            1461610848
        );
    }

    /**
     * @param $side
     * @param $identifier
     */
    protected function logUnsupportedRelationAndExit($side, $identifier)
    {
        $this->logger->error('Unsupported relation!', array('identifier' => $identifier, 'side' => $side));
        throw new \RuntimeException(
            'File [' . $identifier . '] has neither a storage nor a recognized file identifier!',
            1461664911
        );
    }

    /**
     * @param $fileInformation
     */
    protected function logMissingStorageAndExit($fileInformation)
    {
        $this->logger->emergency('Detected a file with missing file storage!', array('fileInfo' => $fileInformation));
        throw new \RuntimeException(
            'Detected a file [' . $fileInformation['identifier'] . '] with non existent file storage ['
            . $fileInformation['storage'] . ']!',
            1461610653
        );
    }

    /**
     * @param Record $record
     * @return array|bool
     */
    protected function getFileInfo(Record $record)
    {
        // we check the two identities because of probably broken relation
        $localIdentifier = $record->getLocalProperty('identifier');
        $foreignIdentifier = $record->getForeignProperty('identifier');

        $result = false;

        // absolute special nearly impossible case first. Both identifiers
        // are null, hence the files do not exist or can not get published
        if (null === $localIdentifier && null === $foreignIdentifier) {
            $this->logger->critical(
                'A file record does neither contain a local nor a foreign identifier',
                array('tableName' => $record->getTableName(), 'identifier' => $record->getIdentifier())
            );
        } elseif (null === $localIdentifier && null !== $foreignIdentifier) {
            // in case the local identifier's null but foreign isn't check
            // if it is a static resource. If not retrieve all information
            if (!$this->containsStaticResource($foreignIdentifier)) {
                $result = array($this->gatherFileInformation(self::FOREIGN, $record), array());
            }
        } elseif (null === $foreignIdentifier && null !== $localIdentifier) {
            // same goes for the local identifier. If it's representing an
            // identifier of a static resource, we skip this file relation
            if (!$this->containsStaticResource($localIdentifier)) {
                $result = array(array(), $this->gatherFileInformation(self::LOCAL, $record));
            }
        } else {
            // If both identifiers exist, gather all information from them
            $result = array(
                $this->gatherFileInformation(self::FOREIGN, $record),
                $this->gatherFileInformation(self::LOCAL, $record),
            );
        }
        return $result;
    }
}
