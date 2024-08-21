<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerServiceInjection;
use In2code\In2publishCore\Component\Core\Resolver\GroupMmMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupSingleTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\SelectStandaloneMmResolver;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_diff;
use function array_key_exists;
use function implode;
use function preg_match;
use function strpos;

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

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
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

        $allowedTables = GeneralUtility::trimExplode(',', $allowed);
        if (empty($tca['MM']) && ['pages'] === $allowedTables) {
            return ['TCA relations to pages are not resolved.'];
        }

        $allowedTables = $this->excludedTablesService->removeExcludedTables($allowedTables);
        if (empty($tca['MM']) && empty($allowedTables)) {
            return ['All tables of this relation (' . $allowed . ') are excluded.'];
        }

        $reasons = [];
        foreach ($allowedTables as $allowedTable) {
            if (!array_key_exists($allowedTable, $GLOBALS['TCA'])) {
                $reasons[] = 'Can not reference the table "' . $allowedTable
                    . '" from "allowed. It is not present in the TCA';
            }
        }
        return $reasons;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver
    {
        $foreignTable = $processedTca['allowed'];
        if ('*' === $foreignTable) {
            $tables = $this->excludedTablesService->getAllNonExcludedTcaTables();
        } else {
            $tables = GeneralUtility::trimExplode(',', $foreignTable);
            $tables = $this->excludedTablesService->removeExcludedTables($tables);
        }

        $tables = array_diff($tables, ['pages']);

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
            $additionalWhere = DatabaseUtility::stripLogicalOperatorPrefix($additionalWhere);
            $additionalWhere = $this->tcaEscapingMarkerService->escapeMarkedIdentifier($additionalWhere);
            if (1 === preg_match(self::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
                $additionalWhere = $matches['where'];
            }

            if ('pages' === $foreignTable || $this->excludedTablesService->isExcludedTable($foreignTable)) {
                $resolver = $this->container->get(SelectStandaloneMmResolver::class);
                $resolver->configure($mmTable, $selectField);
                return $resolver;
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
