<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Utility\DatabaseUtility;

use function preg_match;

class InlineSelectResolver extends AbstractResolver
{
    protected string $foreignTable;
    protected string $foreignField;
    protected ?string $foreignTableField;
    protected string $additionalWhere;

    public function configure(
        string $foreignTable,
        string $foreignField,
        ?string $foreignTableField,
        string $additionalWhere
    ): void {
        $this->foreignTable = $foreignTable;
        $this->foreignField = $foreignField;
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
        $additionalWhere = DatabaseUtility::stripLogicalOperatorPrefix($additionalWhere);
        if (1 === preg_match(AbstractProcessor::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
        }

        $demand = new SelectDemand(
            $this->foreignTable,
            $additionalWhere,
            $this->foreignField,
            $record->getId(),
            $record,
        );
        $demands->addDemand($demand);
    }
}
