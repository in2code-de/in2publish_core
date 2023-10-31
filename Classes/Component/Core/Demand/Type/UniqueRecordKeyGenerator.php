<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand\Type;

use In2code\In2publishCore\Component\Core\Record\Model\Node;

trait UniqueRecordKeyGenerator
{
    protected function createUniqueRecordKey(Node $record): string
    {
        return $record->getClassification() . '\\' . $record->getId();
    }
}
