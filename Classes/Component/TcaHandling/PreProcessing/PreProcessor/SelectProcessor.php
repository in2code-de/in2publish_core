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
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Service\ReplaceMarkersService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_filter;
use function array_key_exists;
use function implode;
use function preg_match;
use function str_starts_with;
use function substr;
use function trim;

class SelectProcessor extends AbstractProcessor
{
    protected $type = 'select';

    protected $forbidden = [
        'itemsProcFunc' => 'itemsProcFunc is not supported',
        'fileFolder' => 'fileFolder is not supported',
        'allowNonIdValues' => 'allowNonIdValues can not be resolved by in2publish',
        'MM_oppositeUsage' => 'MM_oppositeUsage is not supported',
        'special' => 'special is not supported',
    ];

    protected $required = [
        'foreign_table' => 'Can not select without another table',
    ];

    protected $allowed = [
        'foreign_table_where',
        'MM',
        'MM_hasUidField',
        'MM_match_fields',
        'MM_table_where',
        'rootLevel',
        'MM_opposite_field',
    ];

    protected ReplaceMarkersService $replaceMarkersService;

    protected DatabaseIdentifierQuotingService $databaseIdentifierQuotingService;

    public function injectDatabaseIdentifierQuotingService(
        DatabaseIdentifierQuotingService $databaseIdentifierQuotingService
    ): void {
        $this->databaseIdentifierQuotingService = $databaseIdentifierQuotingService;
    }

    public function injectReplaceMarkersService(ReplaceMarkersService $replaceMarkersService): void
    {
        $this->replaceMarkersService = $replaceMarkersService;
    }

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        if (array_key_exists('MM_opposite_field', $tca) && !$this->isSysCategoryField($tca)) {
            return [
                'MM_opposite_field is set on the foreign side of relations, which must not be resolved',
            ];
        }
        return [];
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Closure
    {
        $foreignTable = $processedTca['foreign_table'];
        $foreignTableWhere = $processedTca['foreign_table_where'] ?? '';

        if (isset($processedTca['MM'])) {
            $mmTable = $processedTca['MM'];
            $selectField = ($processedTca['MM_opposite_field'] ?? '') ? 'uid_foreign' : 'uid_local';

            $foreignMatchFields = [];
            foreach ($processedTca['MM_match_fields'] ?? [] as $matchField => $matchValue) {
                if ((string)(int)$matchValue === (string)$matchValue) {
                    $foreignMatchFields[] = $matchField . ' = ' . $matchValue;
                } else {
                    $foreignMatchFields[] = $matchField . ' = "' . $matchValue . '"';
                }
            }
            $additionalWhere = implode(' AND ', $foreignMatchFields);
            $foreignTableWhere = implode(' AND ', array_filter([$foreignTableWhere, $additionalWhere]));
            $foreignTableWhere = trim($foreignTableWhere);
            if (str_starts_with($foreignTableWhere, 'AND ')) {
                $foreignTableWhere = trim(substr($foreignTableWhere, 4));
            }

            return function (DatabaseRecord $record) use (
                $column,
                $mmTable,
                $foreignTable,
                $foreignTableWhere,
                $selectField
            ): ?array {
                $additionalWhere = $this->replaceMarkersService->replaceMarkers($record, $foreignTableWhere, $column);
                $additionalWhere = $this->databaseIdentifierQuotingService->dododo($additionalWhere);
                if (1 === preg_match(self::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
                    $additionalWhere = $matches['where'];
                }

                $demand = [];
                $demand['join'][$mmTable][$foreignTable][$additionalWhere][$selectField][$record->getId()] = $record;
                return $demand;
            };
        }

        return function (DatabaseRecord $record) use ($column, $foreignTable, $foreignTableWhere): array {
            $value = $record->getProp($column);
            if (empty($value)) {
                return [];
            }

            $additionalWhere = $this->replaceMarkersService->replaceMarkers($record, $foreignTableWhere, $column);
            $additionalWhere = trim($additionalWhere);
            if (str_starts_with($additionalWhere, 'AND ')) {
                $additionalWhere = trim(substr($additionalWhere, 4));
            }
            $additionalWhere = $this->databaseIdentifierQuotingService->dododo($additionalWhere);
            if (1 === preg_match(self::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
                $additionalWhere = $matches['where'];
            }

            $demands = [];
            $splittedValues = GeneralUtility::trimExplode(',', $value);
            foreach ($splittedValues as $splittedValue) {
                $demands['select'][$foreignTable][$additionalWhere]['uid'][$splittedValue] = $record;
            }

            return $demands;
        };
    }

    /**
     * Determines if this field is the owning side of a sys_category relation. These relations are automatically
     * generated by `\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable` and therefore distinctive.
     *
     * @param array $config
     *
     * @return bool
     */
    protected function isSysCategoryField(array $config): bool
    {
        return isset($config['foreign_table'], $config['MM_opposite_field'], $config['MM'])
               && 'sys_category' === $config['foreign_table']
               && 'items' === $config['MM_opposite_field']
               && 'sys_category_record_mm' === $config['MM'];
    }
}
