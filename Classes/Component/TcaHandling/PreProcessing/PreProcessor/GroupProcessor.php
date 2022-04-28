<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\DatabaseIdentifierQuotingService;
use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_filter;
use function array_key_exists;
use function array_merge;
use function array_unique;
use function implode;
use function preg_match;
use function strpos;
use function strrpos;
use function substr;
use function trim;

class GroupProcessor extends AbstractProcessor
{
    protected string $type = 'group';

    protected array $required = [
        'allowed' => 'The field "allowed" is required',
    ];

    protected array $forbidden = [
        'MM_opposite_field' => 'MM_opposite_field is set for the foreign side of relations, which must not be resolved',
    ];

    protected array $allowed = [
        'internal_type',
        'MM',
        'MM_hasUidField',
        'MM_match_fields',
        'MM_table_where',
        'uploadfolder',
    ];

    protected DatabaseIdentifierQuotingService $databaseIdentifierQuotingService;

    public function injectDatabaseIdentifierQuotingService(
        DatabaseIdentifierQuotingService $databaseIdentifierQuotingService
    ): void {
        $this->databaseIdentifierQuotingService = $databaseIdentifierQuotingService;
    }

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        $internalType = $tca['internal_type'] ?? 'db';

        if ($internalType !== 'db') {
            return ['The internal type "' . $internalType . '" is not supported'];
        }
        $allowed = $tca['allowed'];

        if ($allowed === '') {
            return ['The field "allowed" is empty'];
        }

        if ($allowed === '*') {
            return [];
        }

        $reasons = [];
        $allowedTables = GeneralUtility::trimExplode(',', $allowed);
        foreach ($allowedTables as $allowedTable) {
            if (!array_key_exists($allowedTable, $GLOBALS['TCA'])) {
                $reasons[] = 'Can not reference the table "' . $allowedTable
                             . '" from "allowed. It is not present in the TCA';
            }
        }
        return $reasons;
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Closure
    {
        $foreignTable = $processedTca['allowed'];
        $isSingleTable = $this->isSingleTable($foreignTable);

        if (isset($processedTca['MM'])) {
            $selectField = ($processedTca['MM_opposite_field'] ?? '') ? 'uid_foreign' : 'uid_local';
            $mmTable = $processedTca['MM'];

            $foreignMatchFields = [];
            foreach ($processedTca['MM_match_fields'] ?? [] as $matchField => $matchValue) {
                if ((string)(int)$matchValue === (string)$matchValue) {
                    $foreignMatchFields[] = $matchField . ' = ' . $matchValue;
                } else {
                    $foreignMatchFields[] = $matchField . ' = "' . $matchValue . '"';
                }
            }
            $additionalWhere = implode(' AND ', $foreignMatchFields);
            $additionalWhere = trim($additionalWhere);
            if (str_starts_with($additionalWhere, 'AND ')) {
                $additionalWhere = trim(substr($additionalWhere, 4));
            }
            $additionalWhere = $this->databaseIdentifierQuotingService->dododo($additionalWhere);
            if (1 === preg_match(self::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
                $additionalWhere = $matches['where'];
            }

            if (!$isSingleTable) {
                return static function (Record $record) use (
                    $mmTable,
                    $column,
                    $selectField,
                    $additionalWhere
                ) {
                    $localValue = $record->getLocalProps()[$column] ?? '';
                    $foreignValue = $record->getForeignProps()[$column] ?? '';

                    $localEntries = GeneralUtility::trimExplode(',', $localValue, true);
                    $foreignEntries = GeneralUtility::trimExplode(',', $foreignValue, true);

                    $demands = [];
                    $values = array_unique(array_filter(array_merge($localEntries, $foreignEntries)));

                    foreach ($values as $value) {
                        $position = strrpos($value, '_');
                        if (false === $position) {
                            continue;
                        }
                        $table = substr($value, 0, $position);
                        $id = substr($value, $position + 1);

                        $demands['join'][$mmTable][$table][$additionalWhere][$selectField][$id][] = $record;
                    }
                    return $demands;
                };
            }

            return static function (Record $record) use (
                $mmTable,
                $foreignTable,
                $selectField,
                $additionalWhere
            ) {
                $demands = [];
                $demands['join'][$mmTable][$foreignTable][$additionalWhere][$selectField][$record->getId()][] = $record;
                return $demands;
            };
        }

        if (!$isSingleTable) {
            return static function (Record $record) use ($column) {
                $localValue = $record->getLocalProps()[$column] ?? '';
                $foreignValue = $record->getForeignProps()[$column] ?? '';

                $localEntries = GeneralUtility::trimExplode(',', $localValue, true);
                $foreignEntries = GeneralUtility::trimExplode(',', $foreignValue, true);

                $demands = [];
                $values = array_unique(array_filter(array_merge($localEntries, $foreignEntries)));
                foreach ($values as $value) {
                    $position = strrpos($value, '_');
                    if (false === $position) {
                        continue;
                    }
                    $table = substr($value, 0, $position);
                    $id = substr($value, $position + 1);

                    $demands['select'][$table]['']['uid'][$id][] = $record;
                }
                return $demands;
            };
        }

        return static function (Record $record) use ($column, $foreignTable) {
            $localValue = $record->getLocalProps()[$column] ?? '';
            $foreignValue = $record->getForeignProps()[$column] ?? '';

            $localEntries = GeneralUtility::trimExplode(',', $localValue, true);
            $foreignEntries = GeneralUtility::trimExplode(',', $foreignValue, true);

            $demands = [];
            $values = array_filter(array_merge($localEntries, $foreignEntries));
            foreach ($values as $value) {
                $demands['select'][$foreignTable]['']['uid'][$value][] = $record;
            }

            return $demands;
        };
    }

    protected function isSingleTable($allowed): bool
    {
        return false === strpos($allowed, '*') && false === strpos($allowed, ',');
    }
}
