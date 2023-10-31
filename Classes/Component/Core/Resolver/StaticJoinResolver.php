<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\JoinDemand;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

class StaticJoinResolver extends AbstractResolver
{
    protected string $mmTable;
    protected string $joinTable;
    protected string $additionalWhere;
    protected string $property;

    public function configure(string $mmTable, string $joinTable, string $additionalWhere, string $property): void
    {
        $this->mmTable = $mmTable;
        $this->joinTable = $joinTable;
        $this->additionalWhere = $additionalWhere;
        $this->property = $property;
    }

    public function getTargetTables(): array
    {
        return [$this->joinTable];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $demand = new JoinDemand(
            $this->mmTable,
            $this->joinTable,
            $this->additionalWhere,
            $this->property,
            $record->getId(),
            $record,
        );
        $demands->addDemand($demand);
    }
}
