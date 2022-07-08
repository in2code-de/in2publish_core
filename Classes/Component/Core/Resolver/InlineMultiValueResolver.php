<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_merge;
use function array_unique;
use function preg_match;
use function substr;
use function trim;

class InlineMultiValueResolver extends AbstractResolver
{
    protected string $foreignTable;
    protected string $column;
    protected ?string $foreignTableField;
    protected string $additionalWhere;

    public function configure(
        string $foreignTable,
        string $column,
        ?string $foreignTableField,
        string $additionalWhere
    ): void {
        $this->foreignTable = $foreignTable;
        $this->column = $column;
        $this->foreignTableField = $foreignTableField;
        $this->additionalWhere = $additionalWhere;
    }

    public function getTargetTables(): array
    {
        return [$this->foreignTable];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $additionalWhere = $this->additionalWhere;
        if (null !== $this->foreignTableField) {
            $additionalWhere .= ' AND ' . $this->foreignTableField . ' = "' . $record->getClassification() . '"';
        }
        $additionalWhere = trim($additionalWhere);
        if (str_starts_with($additionalWhere, 'AND ')) {
            $additionalWhere = trim(substr($additionalWhere, 4));
        }
        if (1 === preg_match(AbstractProcessor::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
        }

        $localValue = $record->getLocalProps()[$this->column];
        $localValues = GeneralUtility::trimExplode(',', $localValue, true);
        $foreignValue = $record->getLocalProps()[$this->column];
        $foreignValues = GeneralUtility::trimExplode(',', $foreignValue, true);
        $values = array_unique(array_merge($localValues, $foreignValues));
        foreach ($values as $value) {
            $demands->addSelect($this->foreignTable, $additionalWhere, 'uid', $value, $record);
        }
    }
}
