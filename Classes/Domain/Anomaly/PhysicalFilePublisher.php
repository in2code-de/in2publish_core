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
use TYPO3\CMS\Core\Log\Logger;
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
     * @var array
     */
    protected $publishedRecords = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
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
     * @return null Returns always null, because slot return values (arrays) are remapped and booleans are not allowed
     * @throws \Exception
     */
    public function publishPhysicalFileOfSysFile($table, Record $record)
    {
        if ('sys_file' === $table) {
            // create a combined identifier, which is unique among all files
            // and might hence be used as cache identifier or similar
            $storage = $record->getMergedProperty('storage');
            $identifier = $record->getMergedProperty('identifier');

            if (strpos($identifier, ',')) {
                list($identifier) = GeneralUtility::trimExplode(',', $identifier);
            }

            $combinedIdentifier = sprintf('%d:%s', $storage, $identifier);

            // If the combined identifier already passed this method is was published, so we can skip it
            if (isset($this->publishedRecords[$table][$combinedIdentifier])) {
                return null;
            }

            $logData = array(
                'table' => $table,
                'uid' => $record->getIdentifier(),
                'storage' => $storage,
                'identifier' => $identifier,
            );

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
                            $this->logger->info('Removed remote file', $logData);
                        } else {
                            $this->logger->error('Failed to remove remote file', $logData);
                        }
                        break;
                    case RecordInterface::RECORD_STATE_ADDED:
                        if (true === $result = $filePublisherService->addFileToForeign($storage, $identifier)) {
                            $this->logger->info('Added file to foreign', $logData);
                        } else {
                            $this->logger->error('Failed to add file to foreign', $logData);
                        }
                        break;
                    case RecordInterface::RECORD_STATE_CHANGED:
                        if (true === $result = $filePublisherService->updateFileOnForeign($storage, $identifier)) {
                            $this->logger->info('Updated file on foreign', $logData);
                        } else {
                            $this->logger->error('Failed to update file to foreign', $logData);
                        }
                        break;
                    case RecordInterface::RECORD_STATE_MOVED:
                        $old = $record->getForeignProperty('identifier');
                        $new = basename($record->getLocalProperty('identifier'));
                        if (true === $result = $filePublisherService->renameForeignFile($storage, $old, $new)) {
                            $this->logger->info('Updated file on foreign', $logData);
                        } else {
                            $this->logger->error('Failed to update file to foreign', $logData);
                        }
                        break;
                    default:
                        // this state includes RecordInterface::RECORD_STATE_UNCHANGED
                        // and any impossible state, which will be ignored
                        $result = true;
                }

                $this->publishedRecords[$table][$combinedIdentifier] = $result;
            } else {
                $this->logger->error('Non authoritative sys_file record detected.', $logData);
            }
        }
        return null;
    }
}
