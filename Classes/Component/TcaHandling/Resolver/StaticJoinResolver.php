<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Domain\Model\Record;

class StaticJoinResolver implements Resolver
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

    public function resolve(Demands $demands, Record $record): void
    {
        $demands->addJoin(
            $this->mmTable,
            $this->joinTable,
            $this->additionalWhere,
            $this->property,
            $record->getId(),
            $record
        );
    }
}
