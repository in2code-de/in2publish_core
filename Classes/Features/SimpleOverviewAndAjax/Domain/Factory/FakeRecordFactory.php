<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Features\SimpleOverviewAndAjax\Domain\Factory;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de
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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Features\SimpleOverviewAndAjax\Domain\Repository\TableCacheRepository;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_diff;
use function array_merge;
use function strnatcmp;
use function strpos;
use function uasort;

/**
 * Class FakeRecordFactory to fake a record tree with just the information from local and just the pages
 */
class FakeRecordFactory
{
    public const PAGE_TABLE_NAME = 'pages';

    /**
     * @var TableCacheRepository
     */
    protected $tableCacheRepository = null;

    /**
     * @var TcaService
     */
    protected $tcaService = null;

    /**
     * @var array
     */
    protected $metaDataBlackList = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * FakeRepository constructor.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->tableCacheRepository = GeneralUtility::makeInstance(TableCacheRepository::class);
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);
        $this->config = GeneralUtility::makeInstance(ConfigContainer::class)->get();
    }

    /**
     * Build a record tree with a minimum information (try to keep queries reduced)
     *
     * @param int $identifier
     *
     * @return Record
     */
    public function buildFromStartPage($identifier): Record
    {
        $record = $this->getSingleFakeRecordFromPageIdentifier($identifier);
        $this->addRelatedRecords($record);
        return $record;
    }

    /**
     * Add related records and respect level depth
     *
     * @param Record $record
     * @param int $currentDepth
     *
     * @return void
     */
    protected function addRelatedRecords(Record $record, $currentDepth = 0)
    {
        $currentDepth++;
        if ($currentDepth < $this->config['factory']['maximumPageRecursion']) {
            foreach ($this->getChildrenPages($record->getIdentifier()) as $pageIdentifier) {
                if ($this->shouldSkipChildrenPage($pageIdentifier)) {
                    $subRecord = $this->getSingleFakeRecordFromPageIdentifier((int)$pageIdentifier);
                    $this->addRelatedRecords($subRecord, $currentDepth);
                    $record->addRelatedRecordRaw($subRecord);
                }
            }
        }
    }

    /**
     * @param int $identifier page identifier
     *
     * @return Record
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getSingleFakeRecordFromPageIdentifier($identifier): Record
    {
        $propertiesLocal = $this->tableCacheRepository->findByUid(static::PAGE_TABLE_NAME, $identifier, 'local');
        $propertiesForeign = $this->tableCacheRepository->findByUid(static::PAGE_TABLE_NAME, $identifier, 'foreign');
        $record = GeneralUtility::makeInstance(
            Record::class,
            'pages',
            $propertiesLocal,
            $propertiesForeign,
            [],
            []
        );
        $this->guessState($record);
        return $record;
    }

    /**
     * Try to get state for given record
     *
     * @param Record $record
     *
     * @return void
     */
    protected function guessState(Record $record)
    {
        if (0 === $record->getIdentifier()) {
            return;
        }
        if ($this->pageIsNew($record)) {
            $record->setState(RecordInterface::RECORD_STATE_ADDED);
        } elseif ($this->pageIsDeletedOnLocalOnly($record->getIdentifier())) {
            $record->setState(RecordInterface::RECORD_STATE_DELETED);
        } elseif ($this->pageHasMoved($record->getIdentifier())) {
            $record->setState(RecordInterface::RECORD_STATE_MOVED);
        } elseif ($this->pageHasChanged($record->getIdentifier()) || $this->pageContentRecordsHasChanged($record)) {
            $record->setState(RecordInterface::RECORD_STATE_CHANGED);
        }
    }

    /**
     * Check if page is new
     *
     * @param Record $record
     *
     * @return bool
     */
    protected function pageIsNew(Record $record): bool
    {
        $propertiesLocal = $this->tableCacheRepository->findByUid(
            static::PAGE_TABLE_NAME,
            $record->getIdentifier(),
            'local'
        );
        $propertiesForeign = $this->tableCacheRepository->findByUid(
            static::PAGE_TABLE_NAME,
            $record->getIdentifier(),
            'foreign'
        );
        return !empty($propertiesLocal) && empty($propertiesForeign);
    }

    /**
     * Get all page identifiers from sub pages
     *
     * @param int $identifier
     *
     * @return array
     */
    protected function getChildrenPages($identifier): array
    {
        $rows = $this->tableCacheRepository->findByPid(static::PAGE_TABLE_NAME, $identifier);
        $rows = $this->sortRowsBySorting($rows);
        $pageIdentifiers = [];
        foreach ($rows as $row) {
            $pageIdentifiers[] = $row['uid'];
        }
        return $pageIdentifiers;
    }

    /**
     * Check if record is deleted and respect delete field from TCA
     *
     * @param int $pageIdentifier
     * @param string $databaseName
     * @param string $tableName
     *
     * @return bool
     */
    protected function isRecordDeleted($pageIdentifier, $databaseName, $tableName = self::PAGE_TABLE_NAME): bool
    {
        $tcaTable = $this->tcaService->getConfigurationArrayForTable($tableName);
        if (!empty($tcaTable['ctrl']['delete'])) {
            $properties = $this->tableCacheRepository->findByUid($tableName, $pageIdentifier, $databaseName);
            return $properties[$tcaTable['ctrl']['delete']] === 1;
        }
        return false;
    }

    /**
     * Compare sorting of a page on both sides. Check if it's different
     *
     * @param int $pageIdentifier
     *
     * @return bool
     */
    protected function pageHasMoved($pageIdentifier): bool
    {
        $propertiesLocal = $this->tableCacheRepository->findByUid(static::PAGE_TABLE_NAME, $pageIdentifier, 'local');
        $propertiesForeign = $this->tableCacheRepository->findByUid(
            static::PAGE_TABLE_NAME,
            $pageIdentifier,
            'foreign'
        );
        return $propertiesLocal['sorting'] !== $propertiesForeign['sorting']
               || $propertiesLocal['pid'] !== $propertiesForeign['pid'];
    }

    /**
     * Check if this page should be related or not
     *
     * @param int $pageIdentifier
     *
     * @return bool
     */
    protected function shouldSkipChildrenPage($pageIdentifier): bool
    {
        return !$this->isRecordDeletedOnBothInstances($pageIdentifier, static::PAGE_TABLE_NAME)
               && !$this->isRecordDeletedOnLocalAndNonExistingOnForeign($pageIdentifier);
    }

    /**
     * Check if page is deleted on local only
     *
     * @param int $pageIdentifier
     *
     * @return bool
     */
    protected function pageIsDeletedOnLocalOnly($pageIdentifier): bool
    {
        $deletedLocal = $this->isRecordDeleted($pageIdentifier, 'local');
        if ($deletedLocal) {
            $deletedForeign = $this->isRecordDeleted($pageIdentifier, 'foreign');
            return $deletedForeign === false;
        }
        return false;
    }

    /**
     * Compare rows of a page on both sides. Check if it's different
     *
     * @param int $pageIdentifier
     *
     * @return bool
     */
    protected function pageHasChanged($pageIdentifier): bool
    {
        $propertiesLocal = $this->tableCacheRepository->findByUid(static::PAGE_TABLE_NAME, $pageIdentifier, 'local');
        $propertiesForeign = $this->tableCacheRepository->findByUid(
            static::PAGE_TABLE_NAME,
            $pageIdentifier,
            'foreign'
        );
        $propertiesLocal = $this->removeIgnoreFieldsFromArray($propertiesLocal, 'pages');
        $propertiesForeign = $this->removeIgnoreFieldsFromArray($propertiesForeign, 'pages');
        $changes = array_diff($propertiesLocal, $propertiesForeign);
        return !empty($changes);
    }

    /**
     * Compare rows of any records on a page. Check if they are different
     *
     * @param Record $record
     *
     * @return bool
     */
    protected function pageContentRecordsHasChanged(Record $record): bool
    {
        $tables = $this->tcaService->getAllTableNamesWithPidAndUidField(
            array_merge($this->config['excludeRelatedTables'], ['pages'])
        );
        foreach ($tables as $table) {
            $propertiesLocal = $this->tableCacheRepository->findByPid($table, $record->getIdentifier(), 'local');
            $propertiesForeign = $this->tableCacheRepository->findByPid($table, $record->getIdentifier(), 'foreign');
            if ($this->areDifferentArrays($propertiesLocal, $propertiesForeign, $table)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if multidimensional array with records is different between instances
     *
     * @param array $arrayLocal
     * @param array $arrayForeign
     * @param string $table
     *
     * @return bool
     */
    protected function areDifferentArrays(array $arrayLocal, array $arrayForeign, $table): bool
    {
        $newLocal = $newForeign = [];

        // remove sys file entries from local extensions and their sys_file_metadata records
        if ('sys_file' === $table) {
            foreach ($arrayLocal as $index => $localSysFile) {
                if (0 === strpos($localSysFile['identifier'], '/typo3conf/ext/') && !isset($arrayForeign[$index])) {
                    $this->metaDataBlackList[$index] = $index;
                    unset($arrayLocal[$index]);
                }
            }
        } elseif ('sys_file_metadata' === $table) {
            foreach ($arrayLocal as $index => $localSysFileMeta) {
                if (isset($this->metaDataBlackList[$localSysFileMeta['file']])) {
                    unset($arrayLocal[$index]);
                }
            }
        }

        foreach ($arrayLocal as $subLocal) {
            $subLocal = $this->removeIgnoreFieldsFromArray($subLocal, $table);
            if (!$this->isRecordDeletedOnLocalAndNonExistingOnForeign($subLocal['uid'], $table)
                && !$this->isRecordDeletedOnBothInstances($subLocal['uid'], $table)
            ) {
                $newLocal[] = $subLocal;
            }
        }
        foreach ($arrayForeign as $subForeign) {
            $subForeign = $this->removeIgnoreFieldsFromArray($subForeign, $table);
            if (!$this->isRecordDeletedOnBothInstances($subForeign['uid'], $table)) {
                $newForeign[] = $subForeign;
            }
        }
        return $newForeign !== $newLocal;
    }

    /**
     * Sort rows array by sorting field
     *
     * @param array $rows
     *
     * @return array
     */
    protected function sortRowsBySorting($rows): array
    {
        uasort(
            $rows,
            function ($row1, $row2) {
                return strnatcmp((string)$row1['sorting'], (string)$row2['sorting']);
            }
        );
        return $rows;
    }

    /**
     * Respect configuration ignoreFieldsForDifferenceView.[table] and remove these fields
     *
     * @param array $properties
     * @param string $table
     *
     * @return array
     */
    protected function removeIgnoreFieldsFromArray(array $properties, $table): array
    {
        if (!empty($this->config['ignoreFieldsForDifferenceView'][$table])) {
            $ignoreFields = $this->config['ignoreFieldsForDifferenceView'][$table];
            $properties = ArrayUtility::removeFromArrayByKey($properties, $ignoreFields);
        }
        return $properties;
    }

    /**
     * Check if record was not generated and at once deleted on local (so it's not existing on foreign)
     *
     * @param int $identifier
     * @param string $tableName
     *
     * @return bool
     */
    protected function isRecordDeletedOnLocalAndNonExistingOnForeign(
        $identifier,
        $tableName = self::PAGE_TABLE_NAME
    ): bool {
        if ($this->isRecordDeleted($identifier, 'local', $tableName)) {
            $properties = $this->tableCacheRepository->findByUid($tableName, $identifier, 'foreign');
            if (empty($properties)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if record is deleted on both instances
     *
     * @param int $identifier
     * @param string $tableName
     *
     * @return bool
     */
    protected function isRecordDeletedOnBothInstances($identifier, $tableName): bool
    {
        return $this->isRecordDeleted($identifier, 'local', $tableName)
               && $this->isRecordDeleted($identifier, 'foreign', $tableName);
    }
}
