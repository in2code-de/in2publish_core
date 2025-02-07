<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerServiceInjection;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\SelectMmResolver;
use In2code\In2publishCore\Component\Core\Resolver\SelectResolver;
use In2code\In2publishCore\Component\Core\Resolver\SelectStandaloneMmResolver;
use In2code\In2publishCore\Utility\DatabaseUtility;

use function array_key_exists;
use function implode;

class SelectProcessor extends AbstractProcessor
{
    use TcaEscapingMarkerServiceInjection;

    protected string $type = 'select';
    protected array $forbidden = [
        'itemsProcFunc' => 'itemsProcFunc is not supported',
        'fileFolder' => 'fileFolder is not supported',
        'allowNonIdValues' => 'allowNonIdValues can not be resolved by in2publish',
        'MM_oppositeUsage' => 'MM_oppositeUsage is not supported',
        'special' => 'special is not supported',
    ];
    protected array $required = [
        'foreign_table' => 'Can not select without another table',
    ];
    protected array $allowed = [
        'foreign_table_where',
        'MM',
        'MM_hasUidField',
        'MM_match_fields',
        'MM_table_where',
        'rootLevel',
        'MM_opposite_field',
    ];

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        if (array_key_exists('MM_opposite_field', $tca) && !$this->isSysCategoryField($tca)) {
            return [
                'MM_opposite_field is set on the foreign side of relations, which must not be resolved',
            ];
        }
        if (array_key_exists('multiple', $tca) && $tca['multiple']) {
            return [
                'Multiple is broken in the TYPO3 Core, https://forge.typo3.org/issues/103604',
            ];
        }

        // Skip relations to table "pages", except if it's the page's transOrigPointerField
        $foreignTable = $tca['foreign_table'] ?? null;
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
        if (empty($tca['MM']) && 'pages' === $foreignTable && ('pages' !== $table || $column !== $transOrigPointerField)) {
            return ['TCA relations to pages are not resolved.'];
        }

        if (empty($tca['MM']) && null !== $foreignTable && $this->excludedTablesService->isExcludedTable($foreignTable)) {
            return ['The table ' . $foreignTable . ' is excluded from publishing'];
        }

        return [];
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Resolver
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
            if (1 === preg_match(AbstractProcessor::ADDITIONAL_ORDER_BY_PATTERN, $foreignTableWhere, $matches) && !empty($matches['where'])) {
                $foreignTableWhere = implode(' AND ', array_filter([$matches['where'], $additionalWhere]));
                if (!empty($matches['col'])) {
                    $foreignTableWhere .= ' ORDER BY ' . $matches['col'] . ($matches['dir'] ?? '');
                }
            } else {
                $foreignTableWhere = implode(' AND ', array_filter([$foreignTableWhere, $additionalWhere]));
            }
            $foreignTableWhere = DatabaseUtility::stripLogicalOperatorPrefix($foreignTableWhere);
            $foreignTableWhere = $this->tcaEscapingMarkerService->escapeMarkedIdentifier($foreignTableWhere);

            if ('pages' === $foreignTable || $this->excludedTablesService->isExcludedTable($foreignTable)) {
                $resolver = $this->container->get(SelectStandaloneMmResolver::class);
                $resolver->configure($mmTable, $selectField);
                return $resolver;
            }

            /** @var SelectMmResolver $resolver */
            $resolver = $this->container->get(SelectMmResolver::class);
            $resolver->configure($foreignTableWhere, $column, $mmTable, $foreignTable, $selectField);
            return $resolver;
        }

        $foreignTableWhere = $this->tcaEscapingMarkerService->escapeMarkedIdentifier($foreignTableWhere);

        /** @var SelectResolver $resolver */
        $resolver = $this->container->get(SelectResolver::class);
        $resolver->configure($column, $foreignTable, $foreignTableWhere);
        return $resolver;
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
