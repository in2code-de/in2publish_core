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
     * TODO: check if the sys_file_processedfile file will be published
     *
     * Check both files and apply the appropriate action:
     *  * Delete the remote file
     *  * Publish the local file
     *  * Update the remote file
     *  * Rename the remote file
     *
     * @param string $table
     * @param Record $record
     * @return null Returns always null, because slot return values (arrays) are remapped and booleans are not allowed
     * @throws \Exception
     */
    public function publishPhysicalFileOfSysFile($table, Record $record)
    {
        if (in_array($table, array('sys_file_processedfile', 'sys_file'))) {
            // create a combined identifier, which is unique among all files
            // and might hence be used as cache identifier or similar
            // TODO evaluate if the merged property also works for renamed files
            $storage = $record->getMergedProperty('storage');
            $identifier = $record->getMergedProperty('identifier');

            if (strpos($identifier, ',')) {
                list($identifier) = GeneralUtility::trimExplode(',', $identifier);
            }

            $combinedIdentifier = $this->createCombinedIdentifier(
                array(
                    'storage' => $storage,
                    'identifier' => $identifier,
                )
            );

            $data = array(
                'table' => $table,
                'uid' => $record->getIdentifier(),
                'storage' => $storage,
                'identifier' => $identifier,
            );

            // If the combined identifier already passed this method is was published, so we can skip it
            if (isset($this->cache[$table][$combinedIdentifier])) {
                return null;
            }

            // The new full FAL support implementation provides us with a sys_file record
            // which comprises all information, given it was created by the FolderRecordFactory
            // if that's the case we can rely on the records state to decide on the action to take.
            if (true === $record->getAdditionalProperty('isAuthoritative')) {
                $filePublisherService = GeneralUtility::makeInstance(
                    'In2code\\In2publishCore\\Domain\\Service\\Publishing\\FilePublisherService'
                );

                switch ($record->getState()) {
                    case RecordInterface::RECORD_STATE_DELETED:
                        if (true === $result = $filePublisherService->removeForeignFile($storage, $identifier)) {
                            $this->logger->info('Removed remote file', $data);
                        } else {
                            $this->logger->error('Failed to remove remote file', $data);
                        }
                        break;
                    case RecordInterface::RECORD_STATE_ADDED:
                        if (true === $result = $filePublisherService->addFileToForeign($storage, $identifier)) {
                            $this->logger->info('Added file to foreign', $data);
                        } else {
                            $this->logger->error('Failed to add file to foreign', $data);
                        }
                        break;
                    case RecordInterface::RECORD_STATE_CHANGED:
                        if (true === $result = $filePublisherService->updateFileOnForeign($storage, $identifier)) {
                            $this->logger->info('Updated file on foreign', $data);
                        } else {
                            $this->logger->error('Failed to update file to foreign', $data);
                        }
                        break;
                    case RecordInterface::RECORD_STATE_MOVED:
                        $old = $record->getForeignProperty('identifier');
                        $new = basename($record->getLocalProperty('identifier'));
                        if (true === $result = $filePublisherService->renameForeignFile($storage, $old, $new)) {
                            $this->logger->info('Updated file on foreign', $data);
                        } else {
                            $this->logger->error('Failed to update file to foreign', $data);
                        }
                        break;
                    case RecordInterface::RECORD_STATE_UNCHANGED:
                        // Do nothing, because there are definitely no changes
                        // between the files since it's state is authoritative
                        // and this case is not reachable by normal operations
                        $result = true;
                        break;
                    default:
                        throw new \Exception(
                            'DEVELOPMENT EXCEPTION: implement publish case for record state ' . $record->getState(),
                            1475677190
                        );
                }
            } else {
                // we check the two identities because of probably broken relation
                $localIdentifier = $record->getLocalProperty('identifier');
                $foreignIdentifier = $record->getForeignProperty('identifier');

                $fileInfo = false;

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
                        $fileInfo = array($this->gatherFileInformation(self::FOREIGN, $record), array());
                    }
                } elseif (null === $foreignIdentifier && null !== $localIdentifier) {
                    // same goes for the local identifier. If it's representing an
                    // identifier of a static resource, we skip this file relation
                    if (!$this->containsStaticResource($localIdentifier)) {
                        $fileInfo = array(array(), $this->gatherFileInformation(self::LOCAL, $record));
                    }
                } else {
                    // If both identifiers exist, gather all information from them
                    $fileInfo = array(
                        $this->gatherFileInformation(self::FOREIGN, $record),
                        $this->gatherFileInformation(self::LOCAL, $record),
                    );
                }

                // in case the file info equals false we were not able to read
                // the required information from the rows and we can't publish
                if (false === $fileInfo) {
                    $this->cache[$table][$combinedIdentifier] = false;
                    return null;
                }

                // If we've got at least one file info we can publish the file
                list($foreignFileInfo, $localFileInfo) = $fileInfo;

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
                    // The hash is exclusively set if the actual file exists on remote
                    if (empty($foreignFileInfo['hash'])) {
                        $this->logger->error(
                            'Tried to delete a non existent foreign file',
                            array('fileInfo' => $foreignFileInfo)
                        );
                    } else {
                        if ($this->sshConnection->removeRemoteFile($foreignFileInfo['relativePath'])) {
                            $this->logger->warning(
                                'Deleted physical file on foreign',
                                array('fileInfo' => $foreignFileInfo)
                            );
                            $result = true;
                        } else {
                            $this->logger->error(
                                'Failed to delete foreign physical file',
                                array('fileInfo' => $foreignFileInfo)
                            );
                        }
                    }
                    $result = false;
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
                                $newIdentifier = $localFileInfo['relativePath'];
                                $oldIdentifier = $foreignFileInfo['relativePath'];

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
                                        $result = true;
                                    } else {
                                        $this->logger->error('Failed to rename remote file', array($logData));
                                    }
                                } else {
                                    $this->logger->error('Failed to create targeted remote folder', array($logData));
                                }
                                $result = false;
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
            }

            if (false === $result) {
                $this->logger->error('Notice: Publishing file failed. See previous message');
            }
            $this->cache[$table][$combinedIdentifier] = $result;
        }
        return null;
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

        if (self::LOCAL === $side) {
            $fileInformation['storage'] = (int)$record->getLocalProperty('storage');
            $fileInformation['identifier'] = $record->getLocalProperty('identifier');
        } elseif (self::FOREIGN === $side) {
            $fileInformation['storage'] = (int)$record->getForeignProperty('storage');
            $fileInformation['identifier'] = $record->getForeignProperty('identifier');
        }

        $storages = $this->storages[$side];
        $identifier = $fileInformation['identifier'];

        // Fallback mode: The storage is zero, which means that the record
        // reference to the file isn't FAL enabled (mostly TCA type group)
        if (0 === $fileInformation['storage']) {
            if (!(0 === strpos($identifier, '/uploads/')
                  || 0 === strpos($identifier, '/typo3temp/')
                  || 0 === strpos($identifier, '/fileadmin/'))
            ) {
                // If there is no configured storage and the file does not
                // represent a legacy resource and is neither a static one
                // we can not resolve the correct file's relative location
                $this->logger->error('Unsupported relation!', array('identifier' => $identifier, 'side' => $side));
                throw new \RuntimeException(
                    'File [' . $identifier . '] has neither a storage nor a recognized file identifier!',
                    1461664911
                );
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
            if (!array_key_exists($fileInformation['storage'], $storages)) {
                // Log and skip the sys_file_storage can not be determined
                $this->logger->emergency(
                    'Detected a file with missing file storage!',
                    array('fileInfo' => $fileInformation)
                );
                throw new \RuntimeException(
                    'Detected a file [' . $fileInformation['identifier'] . '] with non existent file storage ['
                    . $fileInformation['storage'] . ']!',
                    1461610653
                );
            } else {
                $storage = $storages[$fileInformation['storage']];
                if (!'Local' === $storage['driver']) {
                    // Log and skip this file if the Driver is unsupported
                    $this->logger->error(
                        'Only LocalDriver is supported for publishing!',
                        array('fileInfo' => $fileInformation)
                    );
                    throw new \RuntimeException(
                        'File [' . $fileInformation['identifier'] . '] uses a non LocalDriver enabled Storage ['
                        . $fileInformation['storage'] . ']!',
                        1461610848
                    );
                } else {
                    // Convert the storage's FlexForm configuration values
                    // to an array to extract the base path. The base path
                    // will be prepend to the file's identifier to get the
                    // complete path relative to the TYPO3's document root

                    $configuration = $this->flexFormService->convertFlexFormContentToArray(
                        $storage['configuration']
                    );

                    $basePath = $configuration['basePath'];
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
     * @param $side
     * @param $relativeIdentifier
     * @param $fileInformation
     * @return mixed
     */
    protected function addFileInformationByRelativeIdentifier($side, $relativeIdentifier, $fileInformation)
    {
        $fileInformation['relativePath'] = $relativeIdentifier;

        if (self::LOCAL === $side && FileUtility::fileExists($relativeIdentifier)) {
            $hash = FileUtility::hash($relativeIdentifier);
        } elseif (self::FOREIGN === $side && $this->sshConnection->isRemoteFileExisting($relativeIdentifier)) {
            $hash = $this->sshConnection->remoteFileHash($relativeIdentifier);
        } else {
            $hash = false;
        }

        $fileInformation['hash'] = $hash;

        return $fileInformation;
    }

    /**
     * @see \In2code\In2publishCore\Domain\Factory\FolderRecordFactory::createCombinedIdentifier
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
}
