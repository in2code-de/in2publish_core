<?php
declare(strict_types=1);
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
use In2code\In2publishCore\Domain\Service\Publishing\FilePublisherService;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PhysicalFilePublisher
 */
class PhysicalFilePublisher implements SingletonInterface
{
    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var FilePublisherService
     */
    protected $filePublisherService;

    /**
     * @var array
     */
    protected $publishedRecords = [];

    /**
     * Constructor
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->filePublisherService = GeneralUtility::makeInstance(FilePublisherService::class);
    }

    /**
     * Check both files and apply the appropriate action:
     *  * Delete the remote file
     *  * Publish the local file
     *  * Update the remote file
     *  * Rename the remote file
     *
     * @param string $table
     * @param Record $record
     *
     * @return null Returns always null, because slot return values (arrays) are remapped and booleans are not allowed
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function publishPhysicalFileOfSysFile($table, Record $record)
    {
        if ('sys_file' === $table) {
            // create a combined identifier, which is unique among all files
            // and might hence be used as cache identifier or similar
            $storage = $record->getLocalProperty('storage');
            if (!is_numeric($storage)) {
                $storage = $record->getForeignProperty('storage');
            }
            $identifier = $record->getMergedProperty('identifier');

            if (strpos($identifier, ',')) {
                list($identifier) = GeneralUtility::trimExplode(',', $identifier);
            }

            $combinedIdentifier = sprintf('%d:%s', $storage, $identifier);

            // If the combined identifier already passed this method is was published, so we can skip it
            if (isset($this->publishedRecords[$table][$combinedIdentifier])) {
                return null;
            }

            $logData = [
                'table' => $table,
                'uid' => $record->getIdentifier(),
                'storage' => $storage,
                'identifier' => $identifier,
            ];

            // The new full FAL support implementation provides us with a sys_file record
            // which comprises all information, given it was created by the FolderRecordFactory
            // if that's the case we can rely on the records state to decide on the action to take.
            if (true === $record->getAdditionalProperty('isAuthoritative')) {
                $this->publishAuthoritativeRecord($record, $storage, $logData, $identifier, $combinedIdentifier);
            } else {
                $this->logger->error('Non authoritative sys_file record detected.', $logData);
            }
        }
        return null;
    }

    /**
     * @param Record $record
     * @param int $storage
     * @param array $logData
     * @param string|int $identifier
     * @param string $combinedIdentifier
     */
    protected function publishAuthoritativeRecord(Record $record, $storage, $logData, $identifier, $combinedIdentifier)
    {
        switch ($record->getState()) {
            case RecordInterface::RECORD_STATE_MOVED_AND_CHANGED:
                $result = $this->replaceFileWithChangedCopy($record, $storage, $logData);
                break;
            case RecordInterface::RECORD_STATE_DELETED:
                $result = $this->removeFileFromForeign($storage, $logData, $identifier);
                break;
            case RecordInterface::RECORD_STATE_ADDED:
                $result = $this->addFileToForeign($storage, $logData, $identifier);
                break;
            case RecordInterface::RECORD_STATE_CHANGED:
                $result = $this->updateFileOnForeign($storage, $logData, $identifier);
                break;
            case RecordInterface::RECORD_STATE_MOVED:
                $result = $this->moveForeignFile($record, $storage, $logData);
                break;
            default:
                // this state includes RecordInterface::RECORD_STATE_UNCHANGED
                // and any impossible state, which will be ignored
                $result = true;
        }

        $this->publishedRecords[$record->getTableName()][$combinedIdentifier] = $result;
    }

    /**
     * @param Record $record
     * @param int $storage
     * @param array $logData
     *
     * @return bool
     */
    protected function replaceFileWithChangedCopy(Record $record, $storage, $logData)
    {
        $old = $record->getForeignProperty('identifier');
        $new = $record->getLocalProperty('identifier');
        if (true === $result = $this->filePublisherService->addFileToForeign($storage, $new)) {
            $this->logger->info('Added file to foreign', $logData);
            if (true === $result = $this->filePublisherService->removeForeignFile($storage, $old)) {
                $this->logger->info('Removed remote file', $logData);
            } else {
                $this->logger->error('Failed to remove remote file', $logData);
            }
        } else {
            $this->logger->error('Failed to add file to foreign', $logData);
        }
        return $result;
    }

    /**
     * @param int $storage
     * @param array $logData
     * @param string|int $identifier
     *
     * @return bool
     */
    protected function removeFileFromForeign($storage, $logData, $identifier)
    {
        if (true === $result = $this->filePublisherService->removeForeignFile($storage, $identifier)) {
            $this->logger->info('Removed remote file', $logData);
        } else {
            $this->logger->error('Failed to remove remote file', $logData);
        }
        return $result;
    }

    /**
     * @param int $storage
     * @param array $logData
     * @param string|int $identifier
     *
     * @return bool
     */
    protected function addFileToForeign($storage, $logData, $identifier)
    {
        if (true === $result = $this->filePublisherService->addFileToForeign($storage, $identifier)) {
            $this->logger->info('Added file to foreign', $logData);
        } else {
            $this->logger->error('Failed to add file to foreign', $logData);
        }
        return $result;
    }

    /**
     * @param int $storage
     * @param array $logData
     * @param string|int $identifier
     *
     * @return bool
     */
    protected function updateFileOnForeign($storage, $logData, $identifier)
    {
        if (true === $result = $this->filePublisherService->updateFileOnForeign($storage, $identifier)) {
            $this->logger->info('Updated file on foreign', $logData);
        } else {
            $this->logger->error('Failed to update file to foreign', $logData);
        }
        return $result;
    }

    /**
     * @param Record $record
     * @param int $storage
     * @param array $logData
     *
     * @return bool
     */
    protected function moveForeignFile(Record $record, $storage, $logData)
    {
        $old = $record->getForeignProperty('identifier');
        $new = $record->getLocalProperty('identifier');
        if ($old !== $new) {
            $result = $this->filePublisherService->moveForeignFile(
                $storage,
                $old,
                dirname($new),
                basename($new)
            );
            if ($result = ($new === $result)) {
                $this->logger->info('Updated file on foreign', $logData);
            } else {
                $this->logger->error('Failed to update file to foreign', $logData);
            }
        } else {
            $this->logger->warning(
                'File renaming was requested but old and new name are identical',
                array_merge($logData, ['old' => $old, 'new' => $new])
            );
            $result = true;
        }
        return $result;
    }
}
