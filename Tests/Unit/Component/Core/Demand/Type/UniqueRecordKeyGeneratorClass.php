<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Demand\Type;

use In2code\In2publishCore\Component\Core\Demand\Type\UniqueRecordKeyGenerator;
use In2code\In2publishCore\Component\Core\Record\Model\Node;

class UniqueRecordKeyGeneratorClass
{
    use UniqueRecordKeyGenerator {
        createUniqueRecordKey as traitCreateUniqueRecordKey;
    }

    public function createUniqueRecordKey(Node $record): string
    {
        return $this->traitCreateUniqueRecordKey($record);
    }
}
