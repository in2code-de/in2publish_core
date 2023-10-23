<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Service\ReplaceMarkersServiceInject;

use function preg_match;

class SelectMmResolver extends AbstractResolver
{
    use ReplaceMarkersServiceInject;

    protected string $foreignTableWhere;
    protected string $column;
    protected string $mmTable;
    protected string $foreignTable;
    protected string $selectField;

    public function configure(
        string $foreignTableWhere,
        string $column,
        string $mmTable,
        string $foreignTable,
        string $selectField
    ): void {
        $this->foreignTableWhere = $foreignTableWhere;
        $this->column = $column;
        $this->mmTable = $mmTable;
        $this->foreignTable = $foreignTable;
        $this->selectField = $selectField;
    }

    public function getTargetTables(): array
    {
        return [$this->foreignTable];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $additionalWhere = $this->replaceMarkersService->replaceMarkers(
            $record,
            $this->foreignTableWhere,
            $this->column,
        );
        if (1 === preg_match(AbstractProcessor::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
        }

        $value = $record->getId();

        $demands->addJoin($this->mmTable, $this->foreignTable, $additionalWhere, $this->selectField, $value, $record);
    }
}
