<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\PhysicalFilePublisher\Domain\Anomaly;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
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
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Service\Publishing\FilePublisherService;
use In2code\In2publishCore\Event\PhysicalFileWasPublished;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_merge;
use function is_numeric;
use function sprintf;
use function strpos;

class PhysicalFilePublisher implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected FilePublisherService $filePublisherService;

    protected EventDispatcher $eventDispatcher;

    /** @var array<string, array<int|string, bool>> */
    protected array $publishedRecords = [];

    public function __construct(FilePublisherService $filePublisherService, EventDispatcher $eventDispatcher)
    {
        $this->filePublisherService = $filePublisherService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Check both files and apply the appropriate action:
     *  * Delete the remote file
     *  * Publish the local file
     *  * Update the remote file
     *  * Rename the remote file
     *
     * @param PublishingOfOneRecordEnded $event
     *
     * @throws Exception
     */
    public function publishPhysicalFileOfSysFile(PublishingOfOneRecordEnded $event): void
    {
        $record = $event->getRecord();
        $table = $record->getTableName();
        if ('sys_file' !== $table) {
            return;
        }

        // create a combined identifier, which is unique among all files
        // and might hence be used as cache identifier or something similar
        $storage = $record->getLocalProperty('storage');
        if (!is_numeric($storage)) {
            $storage = $record->getForeignProperty('storage');
        }
        $storage = (int)$storage;
        $identifier = $record->getMergedProperty('identifier');

        if (strpos($identifier, ',')) {
            [$identifier] = GeneralUtility::trimExplode(',', $identifier);
        }

        $combinedIdentifier = sprintf('%d:%s', $storage, $identifier);

        // If the combined identifier already passed this method it was published, so we can skip it
        if (isset($this->publishedRecords[$table][$combinedIdentifier])) {
            return;
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
        if (true !== $record->getAdditionalProperty('isAuthoritative')) {
            $this->logger->error('Non authoritative sys_file record detected.', $logData);
            return;
        }

        $this->publishAuthoritativeRecord($record, $storage, $logData, $identifier, $combinedIdentifier);
    }

    protected function publishAuthoritativeRecord(
        RecordInterface $record,
        int $storage,
        array $logData,
        string $identifier,
        string $combinedIdentifier
    ): void {
        if ($record->getAdditionalProperty('fileState') === 'missing') {
            return;
        }
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

        $this->eventDispatcher->dispatch(new PhysicalFileWasPublished($record));

        $this->publishedRecords[$record->getTableName()][$combinedIdentifier] = $result;
    }

    protected function replaceFileWithChangedCopy(RecordInterface $record, int $storage, array $logData): bool
    {
        $old = $record->getForeignProperty('identifier');
        $new = $record->getLocalProperty('identifier');
        $result = $this->filePublisherService->addFileToForeign($storage, $new);
        if (true === $result) {
            $this->logger->info('Added file to foreign', $logData);
            $result = $this->filePublisherService->removeForeignFile($storage, $old);
            if (true === $result) {
                $this->logger->info('Removed remote file', $logData);
            } else {
                $this->logger->error('Failed to remove remote file', $logData);
            }
        } else {
            $this->logger->error('Failed to add file to foreign', $logData);
        }
        return $result;
    }

    protected function removeFileFromForeign(int $storage, array $logData, string $identifier): bool
    {
        $result = $this->filePublisherService->removeForeignFile($storage, $identifier);
        if (true === $result) {
            $this->logger->info('Removed remote file', $logData);
        } else {
            $this->logger->error('Failed to remove remote file', $logData);
        }
        return $result;
    }

    protected function addFileToForeign(int $storage, array $logData, string $identifier): bool
    {
        $result = $this->filePublisherService->addFileToForeign($storage, $identifier);
        if (true === $result) {
            $this->logger->info('Added file to foreign', $logData);
        } else {
            $this->logger->error('Failed to add file to foreign', $logData);
        }
        return $result;
    }

    protected function updateFileOnForeign(int $storage, array $logData, string $identifier): bool
    {
        $result = $this->filePublisherService->updateFileOnForeign($storage, $identifier);
        if (true === $result) {
            $this->logger->info('Updated file on foreign', $logData);
        } else {
            $this->logger->error('Failed to update file to foreign', $logData);
        }
        return $result;
    }

    protected function moveForeignFile(RecordInterface $record, int $storage, array $logData): bool
    {
        $old = $record->getForeignProperty('identifier');
        $new = $record->getLocalProperty('identifier');
        if ($old !== $new) {
            $movingResult = $this->filePublisherService->moveForeignFile($storage, $old, $new);
            $result = $new === $movingResult;
            if (true === $result) {
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
