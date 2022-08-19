<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerServiceInjection;
use In2code\In2publishCore\Component\Core\Resolver\GroupMmMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupSingleTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function implode;
use function preg_match;
use function strpos;
use function substr;
use function trim;

class GroupProcessor extends AbstractProcessor
{
    use TcaEscapingMarkerServiceInjection;

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

    protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver
    {
        $foreignTable = $processedTca['allowed'];
        $tables = GeneralUtility::trimExplode(',', $foreignTable);
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
            $additionalWhere = $this->tcaEscapingMarkerService->escapeMarkedIdentifier($additionalWhere);
            if (1 === preg_match(self::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
                $additionalWhere = $matches['where'];
            }

            if (!$isSingleTable) {
                $resolver = $this->container->get(GroupMmMultiTableResolver::class);
                $resolver->configure($tables, $mmTable, $column, $selectField, $additionalWhere);
                return $resolver;
            }

            $resolver = $this->container->get(StaticJoinResolver::class);
            $resolver->configure($mmTable, $foreignTable, $additionalWhere, $selectField);
            return $resolver;
        }

        if (!$isSingleTable) {
            $resolver = $this->container->get(GroupMultiTableResolver::class);
            $resolver->configure($tables, $column);
            return $resolver;
        }

        $resolver = $this->container->get(GroupSingleTableResolver::class);
        $resolver->configure($column, $foreignTable);
        return $resolver;
    }

    protected function isSingleTable($allowed): bool
    {
        return false === strpos($allowed, '*') && false === strpos($allowed, ',');
    }
}
