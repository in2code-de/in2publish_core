<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\DatabaseIdentifierQuotingService;
use In2code\In2publishCore\Component\TcaHandling\Resolver\InlineMultiValueResolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\InlineSelectResolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\StaticJoinResolver;
use In2code\In2publishCore\Component\TcaHandling\Service\RelevantTablesService;

use function implode;
use function preg_match;
use function substr;
use function trim;

class InlineProcessor extends AbstractProcessor
{
    protected DatabaseIdentifierQuotingService $databaseIdentifierQuotingService;
    protected RelevantTablesService $relevantTablesService;
    protected string $type = 'inline';
    protected array $forbidden = [
        'symmetric_field' => 'symmetric_field is set on the foreign side of relations, which must not be resolved',
    ];
    protected array $required = [
        'foreign_table' => 'Must be set, there is no type "inline" without a foreign table',
    ];
    protected array $allowed = [
        'foreign_field',
        'foreign_match_fields',
        'foreign_table_field',
        'MM',
        'MM_match_fields',
        'MM_opposite_field',
    ];

    public function injectDatabaseIdentifierQuotingService(
        DatabaseIdentifierQuotingService $databaseIdentifierQuotingService
    ): void {
        $this->databaseIdentifierQuotingService = $databaseIdentifierQuotingService;
    }

    public function injectRelevantTablesService(RelevantTablesService $relevantTablesService): void
    {
        $this->relevantTablesService = $relevantTablesService;
    }

    protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver
    {
        $foreignTable = $processedTca['foreign_table'];
        if ($this->relevantTablesService->isEmptyOrExcludedTable($foreignTable)) {
            return null;
        }
        $foreignField = $processedTca['foreign_field'] ?? null;
        $foreignTableField = $processedTca['foreign_table_field'] ?? null;

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

            /** @var StaticJoinResolver $resolver */
            $resolver = $this->container->get(StaticJoinResolver::class);
            $resolver->configure($mmTable, $foreignTable, $additionalWhere, $selectField);
            return $resolver;
        }

        $foreignMatchFields = [];
        foreach ($processedTca['foreign_match_fields'] ?? [] as $matchField => $matchValue) {
            if ((string)(int)$matchValue === (string)$matchValue) {
                $foreignMatchFields[] = $matchField . ' = ' . $matchValue;
            } else {
                $foreignMatchFields[] = $matchField . ' = "' . $matchValue . '"';
            }
        }
        $additionalWhere = implode(' AND ', $foreignMatchFields);
        $additionalWhere = $this->databaseIdentifierQuotingService->dododo($additionalWhere);

        if (null === $foreignField) {
            /** @var InlineMultiValueResolver $resolver */
            $resolver = $this->container->get(InlineMultiValueResolver::class);
            $resolver->configure(
                $foreignTable,
                $column,
                $foreignTableField,
                $additionalWhere
            );
            return $resolver;
        }

        $resolver = $this->container->get(InlineSelectResolver::class);
        $resolver->configure(
            $foreignTable,
            $foreignField,
            $foreignTableField,
            $additionalWhere
        );
        return $resolver;
    }
}
