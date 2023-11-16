<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

interface StaticResolver
{
    public function getTargetClassification(): array;

    public function getTargetProperties(): array;

    public function resolve(Demands $demands, Record $record): void;
}
