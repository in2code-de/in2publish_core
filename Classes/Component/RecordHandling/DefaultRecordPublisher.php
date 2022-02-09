<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RecordHandling;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use In2code\In2publishCore\Event\VoteIfRecordShouldBeSkipped;
use In2code\In2publishCore\Service\Configuration\TcaService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

use function in_array;
use function is_array;
use function strpos;

/**
 * DefaultRecordFinder - published a record recursively including all related records.
 */
class DefaultRecordPublisher extends CommonRepository implements RecordPublisher, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    /** @var Connection */
    protected $foreignDatabase;

    /** @var TcaService */
    protected $tcaService;

    /** @var array */
    private $visitedRecords = [];

    public function __construct(EventDispatcher $eventDispatcher, Connection $foreignDatabase, TcaService $tcaService)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->foreignDatabase = $foreignDatabase;
        $this->tcaService = $tcaService;
    }

    /**
     * Publishes the given Record and all related Records
     * where the related Record's tableName is not excluded
     *
     * @param RecordInterface $record
     * @param array $excludedTables
     *
     * @return void
     * @throws Throwable
     */
    public function publishRecordRecursive(RecordInterface $record, array $excludedTables = ['pages']): void
    {
        try {
            // Dispatch Anomaly
            $this->eventDispatcher->dispatch(new RecursiveRecordPublishingBegan($record, $this));

            $this->publishRecordRecursiveInternal($record, $excludedTables);

            // Dispatch Anomaly
            $this->eventDispatcher->dispatch(new RecursiveRecordPublishingEnded($record, $this));
        } catch (Throwable $exception) {
            $this->logger->critical('Publishing single record failed', ['exception' => $exception]);
            throw $exception;
        }
    }

    protected function publishRecordRecursiveInternal(RecordInterface $record, array $excludedTables): void
    {
        $tableName = $record->getTableName();

        if (
            !empty($this->visitedRecords[$tableName])
            && in_array($record->getIdentifier(), $this->visitedRecords[$tableName])
        ) {
            return;
        }
        $this->visitedRecords[$tableName][] = $record->getIdentifier();

        if (!$this->shouldSkipRecord($record)) {
            // Dispatch Anomaly
            $this->eventDispatcher->dispatch(new PublishingOfOneRecordBegan($record, $this));

            /*
             * For Records shown as moved:
             * Since moved pages only get published explicitly, they will
             * have the state "changed" instead of "moved".
             * Because of this, we don't need to take care about that state
             */

            if ($record->hasAdditionalProperty('recordDatabaseState')) {
                $state = $record->getAdditionalProperty('recordDatabaseState');
            } else {
                $state = $record->getState();
            }

            if (true === $record->getAdditionalProperty('isPrimaryIndex')) {
                $this->logger->notice(
                    'Removing duplicate index from remote',
                    [
                        'tableName' => $tableName,
                        'local_uid' => $record->getLocalProperty('uid'),
                        'foreign_uid' => $record->getForeignProperty('uid'),
                    ]
                );
                // remove duplicate remote index
                $this->deleteRecord($this->foreignDatabase, $record->getForeignProperty('uid'), $tableName);
            }

            if ($state === RecordInterface::RECORD_STATE_CHANGED || $state === RecordInterface::RECORD_STATE_MOVED) {
                $this->updateForeignRecord($record);
            } elseif ($state === RecordInterface::RECORD_STATE_ADDED) {
                $this->addForeignRecord($record);
            } elseif ($state === RecordInterface::RECORD_STATE_DELETED) {
                if ($record->localRecordExists()) {
                    $this->updateForeignRecord($record);
                } elseif ($record->foreignRecordExists()) {
                    $this->deleteForeignRecord($record);
                }
            }

            // Dispatch Anomaly
            $this->eventDispatcher->dispatch(new PublishingOfOneRecordEnded($record, $this));

            // set the records state to published/unchanged to prevent
            // a second INSERT or UPDATE (superfluous queries)
            $record->setState(RecordInterface::RECORD_STATE_UNCHANGED);
        }

        // publish all related records
        $this->publishRelatedRecordsRecursive($record, $excludedTables);
    }

    /**
     * Publishes all related Records of the given record if
     * their tableName is not included in $excludedTables
     *
     * @param RecordInterface $record
     * @param array $excludedTables
     *
     * @return void
     */
    protected function publishRelatedRecordsRecursive(RecordInterface $record, array $excludedTables): void
    {
        foreach ($record->getTranslatedRecords() as $translatedRecord) {
            $this->publishRecordRecursiveInternal($translatedRecord, $excludedTables);
        }

        if (
            $record->hasAdditionalProperty('isRoot')
            && $record->getAdditionalProperty('isRoot') === true
            && !empty($languageField = $this->tcaService->getLanguageField($record->getTableName()))
            && (
                $record->getLocalProperty($languageField) > 0
                || $record->getForeignProperty($languageField) > 0
            )
            && !empty($pointerField = $this->tcaService->getTransOrigPointerField($record->getTableName()))
            && $record->getMergedProperty($pointerField) > 0
        ) {
            $translationOriginals = $record->getRelatedRecordByTableAndProperty(
                $record->getTableName(),
                'uid',
                $record->getMergedProperty($pointerField)
            );
            foreach ($translationOriginals as $translationOriginal) {
                $this->publishRecordRecursiveInternal($translationOriginal, $excludedTables);
            }
        }

        foreach ($record->getRelatedRecords() as $tableName => $relatedRecords) {
            if (is_array($relatedRecords) && !in_array($tableName, $excludedTables, true)) {
                /** @var RecordInterface $relatedRecord */
                foreach ($relatedRecords as $relatedRecord) {
                    $this->publishRecordRecursiveInternal($relatedRecord, $excludedTables);
                }
            }
        }
    }

    /**
     * Publishing Method: Executes an UPDATE query on the
     * foreign Database with all record properties
     *
     * @param RecordInterface $record
     *
     * @return void
     */
    protected function updateForeignRecord(RecordInterface $record): void
    {
        $identifier = $record->getIdentifier();
        $properties = $record->getLocalProperties();
        $tableName = $record->getTableName();
        $this->updateRecord($this->foreignDatabase, $identifier, $properties, $tableName);
    }

    /**
     * Publishing Method: Executes an INSERT query on the
     * foreign database with all record properties
     *
     * @param RecordInterface $record
     *
     * @return void
     */
    protected function addForeignRecord(RecordInterface $record): void
    {
        $tableName = $record->getTableName();
        $properties = $record->getLocalProperties();
        $this->addRecord($this->foreignDatabase, $properties, $tableName);
    }

    /**
     * Publishing Method: Removes a row from the foreign database
     * This can not be undone and the row will be removed forever
     *
     * Since this action is highly destructive, it
     * must be enabled in the Configuration
     *
     * @param RecordInterface $record
     *
     * @return void
     */
    protected function deleteForeignRecord(RecordInterface $record): void
    {
        $identifier = $record->getIdentifier();
        $tableName = $record->getTableName();
        $this->logger->notice(
            'Deleting foreign record',
            [
                'localProperties' => $record->getLocalProperties(),
                'foreignProperties' => $record->getForeignProperties(),
                'identifier' => $identifier,
                'tableName' => $tableName,
            ]
        );
        $this->deleteRecord($this->foreignDatabase, $identifier, $tableName);
    }

    protected function shouldSkipRecord(RecordInterface $record): bool
    {
        $event = new VoteIfRecordShouldBeSkipped($this, $record);
        $this->eventDispatcher->dispatch($event);
        return $event->getVotingResult();
    }

    /**
     * Removes a database row from the given database connection. Executes a DELETE
     * query where uid = $identifier
     * !!! THIS METHOD WILL REMOVE THE MATCHING ROW FOREVER AND IRRETRIEVABLY !!!
     *
     * If you want to delete a row "the normal way" set
     * propertiesArray('deleted' => TRUE) and use updateRecord()
     *
     * @param Connection $connection
     * @param int|string $identifier
     * @param string $tableName
     *
     * @internal param string $deleteFieldName
     */
    protected function deleteRecord(Connection $connection, $identifier, string $tableName): void
    {
        if (strpos((string)$identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);
        } else {
            $identifierArray = ['uid' => (int)$identifier];
        }
        $connection->delete($tableName, $identifierArray);
    }

    /**
     * Executes an UPDATE query on the given database connection. This method will
     * overwrite any value given in $properties where uid = $identifier
     *
     * @param Connection $connection
     * @param int|string $identifier
     * @param array $properties
     * @param string $tableName
     *
     * @return bool
     */
    protected function updateRecord(
        Connection $connection,
        $identifier,
        array $properties,
        string $tableName
    ): bool {
        // deal with MM records, they have (in2publish internal) combined identifiers
        if (strpos((string)$identifier, ',') !== false) {
            $identifierArray = Record::splitCombinedIdentifier($identifier);
        } else {
            $identifierArray = ['uid' => $identifier];
        }
        $connection->update($tableName, $properties, $identifierArray);

        return true;
    }

    /**
     * Executes an INSERT query on the given database connection. Any value in
     * $properties will be inserted into a new row.
     * if there's no UID it will be set by auto_increment
     *
     * @param Connection $connection
     * @param array $properties
     * @param string $tableName
     */
    protected function addRecord(Connection $connection, array $properties, string $tableName): void
    {
        $connection->insert($tableName, $properties);
    }
}
