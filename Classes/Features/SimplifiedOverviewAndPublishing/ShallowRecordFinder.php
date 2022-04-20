<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing;

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

use Closure;
use In2code\In2publishCore\Component\RecordHandling\DefaultRecordFinder;
use In2code\In2publishCore\Component\RecordHandling\RecordFinder;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord;
use In2code\In2publishCore\Event\RecordInstanceWasInstantiated;
use In2code\In2publishCore\Event\RecordWasEnriched;
use In2code\In2publishCore\Event\RootRecordCreationWasFinished;
use In2code\In2publishCore\Event\VoteIfRecordShouldBeIgnored;
use In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\Domain\Repository\DualDatabaseRepository;
use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_diff_assoc;
use function array_keys;
use function array_merge;
use function array_replace_recursive;
use function count;
use function implode;
use function reset;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShallowRecordFinder implements RecordFinder
{
    public const PAGE_TABLE_NAME = 'pages';

    protected TcaService $tcaService;

    protected EventDispatcher $eventDispatcher;

    protected TcaProcessingService $tcaProcessingService;

    protected ShallowFolderRecordFactory $shallowFolderRecordFactory;

    protected DualDatabaseRepository $dualDatabaseRepository;

    /** @var array */
    protected $config;

    public function __construct(
        TcaService $tcaService,
        EventDispatcher $eventDispatcher,
        TcaProcessingService $tcaProcessingService,
        ShallowFolderRecordFactory $shallowFolderRecordFactory,
        DualDatabaseRepository $dualDatabaseRepository,
        ConfigContainer $configContainer
    ) {
        $this->tcaService = $tcaService;
        $this->eventDispatcher = $eventDispatcher;
        $this->tcaProcessingService = $tcaProcessingService;
        $this->shallowFolderRecordFactory = $shallowFolderRecordFactory;
        $this->dualDatabaseRepository = $dualDatabaseRepository;
        $this->config = $configContainer->get();
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findRecordByUidForOverview(int $uid, string $table, bool $excludePages = false): ?RecordInterface
    {
        if (self::PAGE_TABLE_NAME === $table) {
            return $this->findPageRecord($uid, $excludePages);
        }
        // Fallback
        return GeneralUtility::makeInstance(DefaultRecordFinder::class)
            ->findRecordByUidForOverview($uid, $table, $excludePages);
    }

    public function findRecordByUidForPublishing(int $uid, string $table): ?RecordInterface
    {
        return $this->findRecordByUidForOverview($uid, $table, true);
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findRecordsByProperties(array $properties, string $table, bool $simulateRoot = false): array
    {
        // Fallback
        return GeneralUtility::makeInstance(DefaultRecordFinder::class)
            ->findRecordsByProperties($properties, $table, $simulateRoot);
    }

    protected function findPageRecord(int $identifier, bool $excludePages): RecordInterface
    {
        $rootRecord = null;

        $rowSet = ['local' => [], 'foreign' => []];
        if ($identifier !== 0) {
            $rows = $this->dualDatabaseRepository->findByProperty('pages', 'uid', [$identifier]);
            $rowSet = $rows[$identifier];
        }
        $rowSet['additional'] = ['depth' => 1, 'isRoot' => true];
        $this->createRecord(
            'pages',
            $rowSet,
            static function (RecordInterface $record) use (&$rootRecord): void {
                $rootRecord = $record;
            }
        );

        $pageRecords = [
            $rootRecord->getIdentifier() => $rootRecord,
        ];

        if (!$excludePages) {
            $pageRecords = $this->addChildPagesRecursively($pageRecords);
        }

        $tables = $this->tcaService->getAllTableNames(
            array_merge(
                $this->config['excludeRelatedTables'],
                ['pages', 'sys_file', 'sys_file_metadata']
            )
        );

        foreach ($tables as $table) {
            $this->findRelatedRecords($table, array_keys($pageRecords), $pageRecords);
        }
        $this->fetchMmRecords($rootRecord);
        $this->resolveImages($pageRecords);

        foreach ($pageRecords as $pid => $record) {
            $event = new RecordWasEnriched($record);
            $this->eventDispatcher->dispatch($event);
            // The event may replace the record instance
            $pageRecords[$pid] = $record = $event->getRecord();
            $this->eventDispatcher->dispatch(new AllRelatedRecordsWereAddedToOneRecord($record));
        }

        $this->eventDispatcher->dispatch(new RootRecordCreationWasFinished($rootRecord));
        return $rootRecord;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function resolveImages(array $pageRecords): void
    {
        $referenceRecords = [];
        foreach ($pageRecords as $pageRecord) {
            $relatedReferences = $pageRecord->getRelatedRecords()['sys_file_reference'] ?? [];
            foreach ($relatedReferences as $relatedReference) {
                $referenceRecords[$relatedReference->getIdentifier()] = $relatedReference;
            }
        }

        if (empty($referenceRecords)) {
            return;
        }

        /** @var array<int, array<RecordInterface>> $fileToReferenceMap */
        $fileToReferenceMap = [];
        foreach ($referenceRecords as $referenceRecord) {
            $uid = $referenceRecord->getLocalProperty('uid_local') ?: $referenceRecord->getForeignProperty('uid_local');
            $fileToReferenceMap[$uid][] = $referenceRecord;
        }

        $uids = array_keys($fileToReferenceMap);

        if (empty($uids)) {
            return;
        }

        $relatedRecords = $this->dualDatabaseRepository->findByProperty('sys_file', 'uid', $uids);
        $relatedRecords = $this->dualDatabaseRepository->findMissingRows('sys_file', $relatedRecords);

        $sysFileRecords = [];

        $setParentRecord = static function (RecordInterface $record) use (&$sysFileRecords): void {
            $sysFileRecords[] = $record;
        };

        foreach ($relatedRecords as $rowSet) {
            $this->createRecord('sys_file', $rowSet, $setParentRecord);
        }

        $this->shallowFolderRecordFactory->processRecords($sysFileRecords);

        $this->attachMetadata($sysFileRecords);

        foreach ($sysFileRecords as $sysFileRecord) {
            $uid = $sysFileRecord->getIdentifier();
            foreach ($fileToReferenceMap[$uid] ?? [] as $referenceRecord) {
                $referenceRecord->addRelatedRecord($sysFileRecord);
            }
        }
    }

    /**
     * @param array<RecordInterface> $sysFileRecords
     */
    protected function attachMetadata(array $sysFileRecords): void
    {
        if (empty($sysFileRecords)) {
            return;
        }

        $indexedRecords = [];

        foreach ($sysFileRecords as $record) {
            $sysFileUid = $record->getIdentifier();
            $indexedRecords[$sysFileUid] = $record;
        }

        $uids = array_keys($indexedRecords);

        if (empty($uids)) {
            return;
        }

        $relatedRecords = $this->dualDatabaseRepository->findByProperty('sys_file_metadata', 'file', $uids);
        $relatedRecords = $this->dualDatabaseRepository->findMissingRows('sys_file_metadata', $relatedRecords);

        $setParentRecord = static function (RecordInterface $record) use ($indexedRecords): void {
            $sysFileUid = $record->getLocalProperty('file') ?: $record->getForeignProperty('file');
            $indexedRecords[$sysFileUid]->addRelatedRecord($record);
        };

        foreach ($relatedRecords as $rowSet) {
            $this->createRecord('sys_file_metadata', $rowSet, $setParentRecord);
        }
    }

    protected function addChildPagesRecursively(array $pageRecords): array
    {
        $rootRecord = reset($pageRecords);

        $rows = $this->fetchChildPagesRecursively($rootRecord);
        $rows = $this->dualDatabaseRepository->findMissingRows('pages', $rows);

        $setParentRecord = static function (RecordInterface $record) use (&$pageRecords): void {
            $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? null;
            if (null !== $languageField) {
                $language = $record->getLocalProperty($languageField) ?? $record->getForeignProperty($languageField);
                if ($language > 0) {
                    $transOrigPointerField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? null;
                    if (null !== $transOrigPointerField) {
                        $pid = $record->getLocalProperty($transOrigPointerField) ?? $record->getForeignProperty($transOrigPointerField);
                    }
                }
            }
            if (empty($pid)) {
                $pid = $record->getLocalProperty('pid') ?? $record->getForeignProperty('pid');
            }
            $pageRecords[$record->getIdentifier()] = $record;
            $pageRecords[$pid]->addRelatedRecord($record);
        };

        foreach ($rows as $rowSet) {
            $this->createRecord('pages', $rowSet, $setParentRecord);
        }

        return $pageRecords;
    }

    protected function fetchChildPagesRecursively(RecordInterface $rootRecord): array
    {
        $rows = [];
        $depth = 1;

        $iterationRows = [
            $rootRecord->getIdentifier() => [
                'local' => $rootRecord->getLocalProperties(),
                'foreign' => $rootRecord->getForeignProperties(),
            ],
        ];

        do {
            if ($depth++ >= $this->config['factory']['maximumPageRecursion']) {
                break;
            }

            $iterationRows = $this->dualDatabaseRepository->findByProperty('pages', 'pid', array_keys($iterationRows));

            foreach ($iterationRows as $uid => $rowSet) {
                $rows[$uid] = [
                    'additional' => [
                        'depth' => $depth,
                    ],
                    'local' => $rowSet['local'],
                    'foreign' => $rowSet['foreign'],
                ];
            }
        } while (!empty($iterationRows));

        return $rows;
    }

    /**
     * @param string $table
     * @param array $pids
     * @param array<int, RecordInterface> $pageRecords
     */
    protected function findRelatedRecords(string $table, array $pids, array $pageRecords): void
    {
        $relatedRecords = $this->dualDatabaseRepository->findByProperty($table, 'pid', $pids);
        $relatedRecords = $this->dualDatabaseRepository->findMissingRows($table, $relatedRecords);

        $setParentRecord = static function (RecordInterface $record) use ($pageRecords): void {
            $pid = $record->getMergedProperty('pid');
            $parentRecord = $pageRecords[$pid];
            $parentRecord->addRelatedRecord($record);
            $record->addAdditionalProperty('depth', $parentRecord->getAdditionalProperty('depth') + 1);
        };

        foreach ($relatedRecords as $rowSet) {
            $this->createRecord($table, $rowSet, $setParentRecord);
        }
    }

    protected function collectDemands(RecordInterface $rootRecord): array
    {
        $demands = [];
        $tca = $this->tcaProcessingService->getCompatibleTcaParts();
        foreach ($rootRecord->getRelatedRecords() as $table => $records) {
            if (isset($tca[$table])) {
                foreach ($records as $record) {
                    foreach ($tca[$table] as $column => $columnConfig) {
                        $demand = $this->buildDemand($record, $column, $columnConfig);
                        if (null !== $demand) {
                            $demands[] = $demand;
                        }
                    }
                }
            }
        }
        $count = count($demands);
        if ($count > 1) {
            $demands = array_replace_recursive(...$demands);
        } elseif ($count === 1) {
            $demands = $demands[0];
        }
        return $demands;
    }

    /**
     * @param RecordInterface $record
     * @param string $column
     * @param array $columnConfig
     *
     * @return array|null
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function buildDemand(RecordInterface $record, string $column, array $columnConfig): ?array
    {
        $recordIdentifier = $record->getIdentifier();
        if ('select' === $columnConfig['type'] && !empty($columnConfig['MM'])) {
            $additionalWhereParts = [];
            if (!empty($columnConfig['MM_match_fields'])) {
                foreach ($columnConfig['MM_match_fields'] as $field => $value) {
                    $additionalWhereParts[] = "`$field`=\"$value\"";
                }
            }
            $additionalWhere = implode(' AND ', $additionalWhereParts);
            $foreignField = !empty($columnConfig['MM_opposite_field']) ? 'uid_foreign' : 'uid_local';
            return [
                $columnConfig['MM'] => [
                    $additionalWhere => [
                        $foreignField => [
                            'in' => [$recordIdentifier => $recordIdentifier],
                            'valueMap' => [
                                $recordIdentifier => static function (RecordInterface $relatedRecord) use ($record) {
                                    $record->addRelatedRecord($relatedRecord);
                                },
                            ],
                        ],
                    ],
                ],
            ];
        }
        if (
            'group' === $columnConfig['type']
            && 'db' === $columnConfig['internal_type']
            && !empty($columnConfig['MM'])
            // These fields are not supported
            && empty($columnConfig['MM_table_where'])
            && empty($columnConfig['MM_hasUidField'])
        ) {
            $foreignField = !empty($columnConfig['MM_opposite_field']) ? 'uid_foreign' : 'uid_local';
            $additionalWhereParts = [];
            if (!empty($columnConfig['MM_match_fields'])) {
                foreach ($columnConfig['MM_match_fields'] as $field => $value) {
                    $additionalWhereParts[] = "`$field`=\"$value\"";
                }
            }
            $additionalWhere = implode(' AND ', $additionalWhereParts);
            return [
                $columnConfig['MM'] => [
                    $additionalWhere => [
                        $foreignField => [
                            'in' => [$recordIdentifier => $recordIdentifier],
                            'valueMap' => [
                                $recordIdentifier => static function (RecordInterface $relatedRecord) use ($record) {
                                    $record->addRelatedRecord($relatedRecord);
                                },
                            ],
                        ],
                    ],
                ],
            ];
        }
        return null;
    }

    protected function fetchMmRecords(RecordInterface $rootRecord): void
    {
        $demands = $this->collectDemands($rootRecord);

        foreach ($demands as $table => $aggregatedDemand) {
            foreach ($aggregatedDemand as $additionalWhere => $fieldRequests) {
                foreach ($fieldRequests as $field => $fieldParam) {
                    $this->resolveDemand($table, $additionalWhere, $field, $fieldParam);
                }
            }
        }
    }

    protected function resolveDemand(string $table, string $additionalWhere, string $field, array $request): void
    {
        $relatedRows = $this->dualDatabaseRepository->findMm($table, $field, $request['in'], $additionalWhere);
        $this->mapRelatedRecordsByRequest($table, $relatedRows, $field, $request['valueMap']);
    }

    protected function mapRelatedRecordsByRequest(string $table, array $rows, string $property, array $valueMap): void
    {
        foreach ($rows as $rowSet) {
            $requestedProperty = $rowSet['local'][$property] ?? $rows['foreign'][$property];
            if (isset($valueMap[$requestedProperty])) {
                $this->createRecord($table, $rowSet, $valueMap[$requestedProperty]);
            }
        }
    }

    protected function createRecord(string $table, array $rowSet, Closure $setParentRecord): void
    {
        if ($this->isIgnoredRecord($table, $rowSet)) {
            return;
        }

        $record = new Record(
            $table,
            $rowSet['local'],
            $rowSet['foreign'],
            $GLOBALS['TCA'][$table] ?? [],
            $rowSet['additional']
        );

        $setParentRecord($record);

        $this->eventDispatcher->dispatch(new RecordInstanceWasInstantiated($record));
    }

    protected function isIgnoredRecord(string $table, array $rowSet): bool
    {
        if ($this->isDeletedAndUnchangedRecord($table, $rowSet)) {
            return true;
        }

        if ($this->isRemovedAndDeletedRecord($table, $rowSet)) {
            return true;
        }

        if ($this->shouldIgnoreRecord($table, $rowSet)) {
            return true;
        }

        return false;
    }

    protected function isDeletedAndUnchangedRecord(string $table, array $rowSet): bool
    {
        $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? null;
        if (null === $deleteField || !$rowSet['local'][$deleteField]) {
            return false;
        }
        $differences = array_diff_assoc($rowSet['local'], $rowSet['foreign']);
        return empty($differences);
    }

    protected function isRemovedAndDeletedRecord(string $table, array $rowSet): bool
    {
        $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? null;
        if (null === $deleteField || !$rowSet['local'][$deleteField]) {
            return false;
        }
        if ($this->config['factory']['treatRemovedAndDeletedAsDifference']) {
            // "Removed and deleted" only refers to the local side.
            // If the record is not exactly 1. deleted on foreign and 2. removed on local this feature does not apply.
            /** @noinspection NestedPositiveIfStatementsInspection */
            if (empty($rowSet['local']) && $rowSet['foreign'][$deleteField]) {
                return false;
            }
        }
        return (empty($rowSet['local']) && $rowSet['foreign'][$deleteField]) || empty($rowSet['foreign']);
    }

    protected function shouldIgnoreRecord(string $table, array $rowSet): bool
    {
        $event = new VoteIfRecordShouldBeIgnored($this, $rowSet['local'], $rowSet['foreign'], $table);
        $this->eventDispatcher->dispatch($event);
        return $event->getVotingResult();
    }
}
