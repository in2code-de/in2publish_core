<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerServiceInjection;
use In2code\In2publishCore\Component\Core\Resolver\InlineMultiValueResolver;
use In2code\In2publishCore\Component\Core\Resolver\InlineSelectResolver;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;

use function implode;
use function preg_match;
use function substr;
use function trim;

class InlineProcessor extends AbstractProcessor
{
    use TcaEscapingMarkerServiceInjection;

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

    protected function buildResolver(string $table, string $column, array $processedTca): Resolver
    {
        $foreignTable = $processedTca['foreign_table'];
        $foreignField = $processedTca['foreign_field'] ?? null;
        $foreignTableField = $processedTca['foreign_table_field'] ?? null;

        if (isset($processedTca['MM'])) {
            $selectField = ($processedTca['MM_opposite_field'] ?? '') ? 'uid_foreign' : 'uid_local';
            $mmTable = $processedTca['MM'];

            $additionalWhere = $this->processMmMatchFields($processedTca);

            /** @var StaticJoinResolver $resolver */
            $resolver = $this->container->get(StaticJoinResolver::class);
            $resolver->configure($mmTable, $foreignTable, $additionalWhere, $selectField);
            return $resolver;
        }

        $additionalWhere = $this->processForeignMatchFields($processedTca);

        if (null === $foreignField) {
            /** @var InlineMultiValueResolver $resolver */
            $resolver = $this->container->get(InlineMultiValueResolver::class);
            $resolver->configure(
                $foreignTable,
                $column,
                $foreignTableField,
                $additionalWhere,
            );
            return $resolver;
        }

        $resolver = $this->container->get(InlineSelectResolver::class);
        $resolver->configure(
            $foreignTable,
            $foreignField,
            $foreignTableField,
            $additionalWhere,
        );
        return $resolver;
    }

    public function processMmMatchFields(array $processedTca): string
    {
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
        return $additionalWhere;
    }

    protected function processForeignMatchFields(array $processedTca): string
    {
        $foreignMatchFields = [];
        foreach ($processedTca['foreign_match_fields'] ?? [] as $matchField => $matchValue) {
            if ((string)(int)$matchValue === (string)$matchValue) {
                $foreignMatchFields[] = $matchField . ' = ' . $matchValue;
            } else {
                $foreignMatchFields[] = $matchField . ' = "' . $matchValue . '"';
            }
        }
        $additionalWhere = implode(' AND ', $foreignMatchFields);
        return $this->tcaEscapingMarkerService->escapeMarkedIdentifier($additionalWhere);
    }
}
