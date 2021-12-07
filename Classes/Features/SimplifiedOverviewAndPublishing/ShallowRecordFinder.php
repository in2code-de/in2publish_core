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

use In2code\In2publishCore\Component\RecordHandling\DefaultRecordFinder;
use In2code\In2publishCore\Component\RecordHandling\RecordFinder;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Service\Database\RawRecordService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_column;
use function array_keys;
use function array_merge;
use function array_replace_recursive;
use function array_unique;
use function count;
use function implode;
use function sort;

class ShallowRecordFinder implements RecordFinder
{
    public const PAGE_TABLE_NAME = 'pages';

    /** @var Connection */
    protected $localDatabase;

    /** @var Connection */
    protected $foreignDatabase;

    /** @var TcaService */
    protected $tcaService;

    /** @var RawRecordService */
    protected $rawRecordService;

    /** @var array */
    protected $config;

    public function __construct(
        TcaService $tcaService,
        RawRecordService $rawRecordService,
        ConfigContainer $configContainer
    ) {
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        $this->tcaService = $tcaService;
        $this->rawRecordService = $rawRecordService;
        $this->config = $configContainer->get();
    }

    public function findRecordByUid(
        int $uid,
        string $table,
        bool $disablePageRecursion = false
    ): ?RecordInterface {
        if (self::PAGE_TABLE_NAME === 'pages') {
            return $this->findPageRecord($uid, $disablePageRecursion);
        }
        // Fallback
        return GeneralUtility
            ::makeInstance(DefaultRecordFinder::class)
            ->findRecordByUid($uid, $table, $disablePageRecursion);
    }

    public function findRecordsByProperties(array $properties, string $table, bool $simulateRoot = false): array
    {
        // Fallback
        return GeneralUtility
            ::makeInstance(DefaultRecordFinder::class)
            ->findRecordsByProperties($properties, $table, $simulateRoot);
    }

    protected function findPageRecord(int $identifier, bool $disablePageRecursion): RecordInterface
    {
        $depth = 0;

        $localProperties = $this->rawRecordService->getRawRecord('pages', $identifier, 'local') ?? [];
        $foreignProperties = $this->rawRecordService->getRawRecord('pages', $identifier, 'foreign') ?? [];
        $pagesTca = $this->tcaService->getConfigurationArrayForTable('pages');
        $record = new Record(
            'pages',
            $localProperties,
            $foreignProperties,
            $pagesTca,
            ['depth' => $depth]
        );
        unset($localProperties, $foreignProperties);
        $records[$record->getIdentifier()] = $record;

        $deleteField = $pagesTca['ctrl']['delete'] ?? null;
        $rows = [];

        $relatedRecords = [
            $record->getIdentifier() => [
                'local' => $record->getLocalProperties(),
                'foreign' => $record->getForeignProperties(),
            ],
        ];
        do {
            if ($depth++ >= $this->config['factory']['maximumPageRecursion'] || $disablePageRecursion) {
                break;
            }

            $query = $this->localDatabase->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('*')
                  ->from('pages')
                  ->where($query->expr()->in('pid', array_keys($relatedRecords)));
            $result = $query->execute();
            $localRows = array_column($result->fetchAllAssociative(), null, 'uid');

            $query = $this->foreignDatabase->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('*')
                  ->from('pages')
                  ->where($query->expr()->in('pid', array_keys($relatedRecords)));
            $result = $query->execute();
            $foreignRows = array_column($result->fetchAllAssociative(), null, 'uid');

            $relatedRecords = [];
            $commonUids = array_unique(array_merge(array_keys($localRows), array_keys($foreignRows)));
            foreach ($commonUids as $uid) {
                $relatedRecords[$uid] = [
                    'local' => $localRows[$uid] ?? [],
                    'foreign' => $foreignRows[$uid] ?? [],
                ];
                $rows[$uid] = [
                    'additionalProperties' => [
                        'depth' => $depth,
                    ],
                    'local' => $localRows[$uid] ?? [],
                    'foreign' => $foreignRows[$uid] ?? [],
                ];
            }
        } while (!empty($relatedRecords));

        $missingRecords = [];
        foreach ($rows as $uid => $rowSet) {
            if ([] === $rowSet['local']) {
                $missingRecords['local'][$uid] = $uid;
            } elseif ([] === $rowSet['foreign']) {
                $missingRecords['foreign'][$uid] = $uid;
            }
        }
        if (!empty($missingRecords['local'])) {
            $query = $this->localDatabase->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('*')
                  ->from('pages')
                  ->where($query->expr()->in('uid', $missingRecords['local']));
            $result = $query->execute();
            foreach ($result->fetchAllAssociative() as $row) {
                $rows[$row['uid']]['local'] = $row;
            }
        }
        if (!empty($missingRecords['foreign'])) {
            $query = $this->foreignDatabase->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('*')
                  ->from('pages')
                  ->where($query->expr()->in('uid', $missingRecords['foreign']));
            $result = $query->execute();
            foreach ($result->fetchAllAssociative() as $row) {
                $rows[$row['uid']]['foreign'] = $row;
            }
        }

        foreach ($rows as $uid => $rowSet) {
            $localProperties = $rowSet['local'];
            $foreignProperties = $rowSet['foreign'];
            if (
                ([] === $localProperties || (null !== $deleteField && $localProperties[$deleteField]))
                && ([] === $foreignProperties || (null !== $deleteField && $foreignProperties[$deleteField]))
            ) {
                continue;
            }

            $relatedRecord = new Record(
                'pages',
                $localProperties,
                $foreignProperties,
                $pagesTca,
                $rowSet['additionalProperties']
            );
            $records[$uid] = $relatedRecord;
            $pid = $relatedRecord->getMergedProperty('pid');
            $records[$pid]->addRelatedRecord($relatedRecord);
        }

        $pids = array_keys($records);
        sort($pids);

        $tables = $this->tcaService->getAllTableNames(
            array_merge(
                $this->config['excludeRelatedTables'],
                ['pages', 'sys_file', 'sys_file_metadata']
            )
        );

        foreach ($tables as $table) {
            $query = $this->localDatabase->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('*')
                  ->from($table)
                  ->where($query->expr()->in('pid', $pids))
                  ->orderBy('uid');
            $result = $query->execute();
            $localRows = array_column($result->fetchAllAssociative(), null, 'uid');

            $query = $this->foreignDatabase->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('*')
                  ->from($table)
                  ->where($query->expr()->in('pid', $pids))
                  ->orderBy('uid');
            $result = $query->execute();
            $foreignRows = array_column($result->fetchAllAssociative(), null, 'uid');

            $commonUids = array_unique(array_merge(array_keys($localRows), array_keys($foreignRows)));
            $relatedRecords = [];
            foreach ($commonUids as $uid) {
                $relatedRecords[$uid] = [
                    'local' => $localRows[$uid] ?? [],
                    'foreign' => $foreignRows[$uid] ?? [],
                ];
            }

            $missingRecords = [];
            foreach ($relatedRecords as $uid => $rowSet) {
                if ([] === $rowSet['local']) {
                    $missingRecords['local'][$uid] = $uid;
                } elseif ([] === $rowSet['foreign']) {
                    $missingRecords['foreign'][$uid] = $uid;
                }
            }
            if (!empty($missingRecords['local'])) {
                $query = $this->localDatabase->createQueryBuilder();
                $query->getRestrictions()->removeAll();
                $query->select('*')
                      ->from('pages')
                      ->where($query->expr()->in('uid', $missingRecords['local']));
                $result = $query->execute();
                foreach ($result->fetchAllAssociative() as $row) {
                    $relatedRecords[$row['uid']]['local'] = $row;
                }
            }
            if (!empty($missingRecords['foreign'])) {
                $query = $this->foreignDatabase->createQueryBuilder();
                $query->getRestrictions()->removeAll();
                $query->select('*')
                      ->from('pages')
                      ->where($query->expr()->in('uid', $missingRecords['foreign']));
                $result = $query->execute();
                foreach ($result->fetchAllAssociative() as $row) {
                    $relatedRecords[$row['uid']]['foreign'] = $row;
                }
            }

            foreach ($relatedRecords as $rowSet) {
                $relatedRecord = new Record(
                    $table,
                    $rowSet['local'] ?? [],
                    $rowSet['foreign'] ?? [],
                    $GLOBALS['TCA'][$table],
                    ['depth' => $depth]
                );
                $pid = $relatedRecord->getPageIdentifier();
                $records[$pid]->addRelatedRecord($relatedRecord);
            }
        }
        $this->fetchMmRecords($record);
        return $record;
    }

    protected function collectDemands(RecordInterface $basicRecord): array
    {
        $demands = [];
        $tca = TcaProcessingService::getCompatibleTca();
        foreach ($basicRecord->getRelatedRecords() as $table => $records) {
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

    protected function fetchMmRecords(RecordInterface $basicRecord): void
    {
        $demands = $this->collectDemands($basicRecord);

        foreach ($demands as $table => $aggregatedDemand) {
            foreach ($aggregatedDemand as $additionalWhere => $fieldRequests) {
                foreach ($fieldRequests as $field => $fieldParam) {
                    $this->resolveDemand($table, $additionalWhere, $field, $fieldParam);
                }
            }
        }
    }

    protected function resolveDemand(string $table, string $additionalWhere, string $field, array $fieldParam): void
    {
        $relatedRows = [
            'local' => [],
            'foreign' => [],
        ];
        $query = DatabaseUtility::buildLocalDatabaseConnection()->createQueryBuilder();
        $localRows = $query
            ->select('*')
            ->from($table)
            ->where($additionalWhere)
            ->andWhere($query->expr()->in($field, $fieldParam['in']))
            ->execute()
            ->fetchAllAssociative();
        foreach ($localRows as $localRow) {
            $relatedRows['local'][$this->buildRecordIndexIdentifier($localRow)] = $localRow;
        }
        $query = DatabaseUtility::buildForeignDatabaseConnection()->createQueryBuilder();
        $foreignRows = $query
            ->select('*')
            ->from($table)
            ->where($additionalWhere)
            ->andWhere($query->expr()->in($field, $fieldParam['in']))
            ->execute()
            ->fetchAllAssociative();
        foreach ($foreignRows as $foreignRow) {
            $relatedRows['foreign'][$this->buildRecordIndexIdentifier($foreignRow)] = $foreignRow;
        }
        $this->mapRelatedRecordsByFieldRequest($relatedRows, $field, $fieldParam, $table);
    }

    protected function buildRecordIndexIdentifier(array $row): string
    {
        if (!isset($row['uid'])) {
            $parts = [
                $row['uid_local'],
                $row['uid_foreign'],
            ];
            if (isset($row['sorting'])) {
                $parts[] = $row['sorting'];
            }
            return implode(',', $parts);
        }
        return (string)$row['uid'];
    }

    protected function mapRelatedRecordsByFieldRequest(
        array $relatedRows,
        string $field,
        array $values,
        string $table
    ): void {
        $keys = array_merge(array_keys($relatedRows['local']), array_keys($relatedRows['foreign']));
        foreach ($keys as $key) {
            $valueMapIndex = null;
            if (isset($relatedRows['local'][$key][$field])) {
                $valueMapIndex = $relatedRows['local'][$key][$field];
            } elseif (isset($relatedRows['foreign'][$key][$field])) {
                $valueMapIndex = $relatedRows['foreign'][$key][$field];
            }
            if (null !== $valueMapIndex && isset($values['valueMap'][$valueMapIndex])) {
                $localProperties = $relatedRows['local'][$key] ?? [];
                $foreignProperties = $relatedRows['foreign'][$key] ?? [];
                $record = new Record($table, $localProperties, $foreignProperties, [], []);
                $values['valueMap'][$valueMapIndex]($record);
            }
        }
    }
}
