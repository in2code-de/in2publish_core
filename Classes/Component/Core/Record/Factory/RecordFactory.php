<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Factory;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
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

use In2code\In2publishCore\CommonInjection\EventDispatcherInjection;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\MmDatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\PageTreeRootRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\Record\Model\StorageRootFolderRecord;
use In2code\In2publishCore\Component\Core\RecordIndexInjection;
use In2code\In2publishCore\Event\DecideIfRecordShouldBeIgnored;
use In2code\In2publishCore\Event\RecordWasCreated;
use In2code\In2publishCore\Service\Configuration\IgnoredFieldsServiceInjection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RecordFactory
{
    use RecordIndexInjection;
    use EventDispatcherInjection;
    use IgnoredFieldsServiceInjection;

    protected DatabaseRecordFactoryFactory $databaseRecordFactoryFactory;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function __construct(\In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactoryFactory $databaseRecordFactoryFactory)
    {
        $this->databaseRecordFactoryFactory = $databaseRecordFactoryFactory;
    }

    public function createPageTreeRootRecord(): PageTreeRootRecord
    {
        $record = new PageTreeRootRecord();
        $this->finishRecord($record);
        return $record;
    }

    public function createDatabaseRecord(
        string $table,
        int $id,
        array $localProps,
        array $foreignProps
    ): ?DatabaseRecord {
        $tableIgnoredFields = $this->ignoredFieldsService->getIgnoredFields($table);
        $factory = $this->databaseRecordFactoryFactory->createFactoryForTable($table);
        $record = $factory->createDatabaseRecord($table, $id, $localProps, $foreignProps, $tableIgnoredFields);
        if ($this->shouldIgnoreRecord($record)) {
            return null;
        }
        $this->finishRecord($record);
        return $record;
    }

    public function createMmRecord(
        string $table,
        string $propertyHash,
        array $localProps,
        array $foreignProps
    ): ?MmDatabaseRecord {
        $record = new MmDatabaseRecord($table, $propertyHash, $localProps, $foreignProps);
        if ($this->shouldIgnoreRecord($record)) {
            return null;
        }
        $this->finishRecord($record);
        return $record;
    }

    public function createFileRecord(array $localProps, array $foreignProps): ?FileRecord
    {
        $ignoredFields = $this->ignoredFieldsService->getIgnoredFields(FileRecord::CLASSIFICATION);
        $record = new FileRecord($localProps, $foreignProps, $ignoredFields);
        if ($this->shouldIgnoreRecord($record)) {
            return null;
        }
        $this->finishRecord($record);
        return $record;
    }

    public function createFolderRecord(array $localProps, array $foreignProps): ?FolderRecord
    {
        if ('/' === ($localProps['identifier'] ?? null) || '/' === ($foreignProps['identifier'] ?? null)) {
            $record = new StorageRootFolderRecord($localProps, $foreignProps);
        } else {
            $record = new FolderRecord($localProps, $foreignProps);
        }
        if ($this->shouldIgnoreRecord($record)) {
            return null;
        }
        $this->finishRecord($record);
        return $record;
    }

    protected function shouldIgnoreRecord(Record $record): bool
    {
        if ($this->isDeletedAndUnchangedRecord($record)) {
            return true;
        }

        if ($this->isRemovedAndDeletedRecord($record)) {
            return true;
        }

        $event = new DecideIfRecordShouldBeIgnored($record);
        $this->eventDispatcher->dispatch($event);
        if ($event->shouldBeIgnored()) {
            return true;
        }

        return false;
    }

    protected function finishRecord(Record $record): void
    {
        $this->recordIndex->addRecord($record);
        $this->eventDispatcher->dispatch(new RecordWasCreated($record));
    }

    protected function isDeletedAndUnchangedRecord(Record $record): bool
    {
        $deleteField = $GLOBALS['TCA'][$record->getClassification()]['ctrl']['delete'] ?? null;
        return null !== $deleteField
            && array_key_exists($deleteField, $record->getLocalProps())
            && $record->getLocalProps()[$deleteField]
            && !count(array_diff($record->getLocalProps(), $record->getForeignProps()));
    }

    protected function isRemovedAndDeletedRecord(Record $record): bool
    {
        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);

        if (!$configContainer->get('features.hideRecordsDeletedDifferently.enable')) {
            return false;
        }
        return (empty($record->getLocalProps()) && isset($record->getForeignProps()['deleted']) && 1 === (int)$record->getForeignProps()['deleted'])
            || (empty($record->getForeignProps()) && isset($record->getLocalProps()['deleted']) && 1 === (int)$record->getLocalProps()['deleted']);
    }
}
