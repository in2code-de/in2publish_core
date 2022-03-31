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

use function implode;
use function preg_match;
use function substr;
use function trim;

class InlineProcessor extends AbstractProcessor
{
    protected $type = 'inline';

    protected $forbidden = [
        'symmetric_field' => 'symmetric_field is set on the foreign side of relations, which must not be resolved',
    ];

    protected $required = [
        'foreign_table' => 'Must be set, there is no type "inline" without a foreign table',
    ];

    protected $allowed = [
        'foreign_field',
        'foreign_match_fields',
        'foreign_table_field',
        'MM',
        'MM_match_fields',
        'MM_opposite_field',
    ];
    protected DatabaseIdentifierQuotingService $databaseIdentifierQuotingService;

    public function injectDatabaseIdentifierQuotingService(DatabaseIdentifierQuotingService $databaseIdentifierQuotingService): void
    {
        $this->databaseIdentifierQuotingService = $databaseIdentifierQuotingService;
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Closure
    {
        $foreignTable = $processedTca['foreign_table'];
        $foreignField = $processedTca['foreign_field'];

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

            return static function (DatabaseRecord $record) use (
                $mmTable,
                $foreignTable,
                $selectField,
                $additionalWhere
            ) {
                $demands = [];
                $demands['join'][$mmTable][$foreignTable][$additionalWhere][$selectField][$record->getId()] = $record;
                return $demands;
            };
        }

        $foreignTableField = $processedTca['foreign_table_field'] ?? null;

        $foreignMatchFields = [];
        foreach ($processedTca['foreign_match_fields'] ?? [] as $matchField => $matchValue) {
            if ((string)(int)$matchValue === (string)$matchValue) {
                $foreignMatchFields[] = $matchField . ' = ' . $matchValue;
            } else {
                $foreignMatchFields[] = $matchField . ' = "' . $matchValue . '"';
            }
        }
        $additionalWhere = implode(' AND ', $foreignMatchFields);

        return function (DatabaseRecord $record) use ($foreignTable, $foreignField, $foreignTableField, $additionalWhere) {
            if (null !== $foreignTableField) {
                $additionalWhere .= ' AND ' . $foreignTableField . ' = "' . $record->getTable() . '"';
            }
            $additionalWhere = trim($additionalWhere);
            if (str_starts_with($additionalWhere, 'AND ')) {
                $additionalWhere = trim(substr($additionalWhere, 4));
            }
            $additionalWhere = $this->databaseIdentifierQuotingService->dododo($additionalWhere);
            if (1 === preg_match(self::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
                $additionalWhere = $matches['where'];
            }

            $demands = [];
            $demands['select'][$foreignTable][$additionalWhere][$foreignField][$record->getId()] = $record;
            return $demands;
        };
    }
}
