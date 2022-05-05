<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Domain\Model\Record;

interface Resolver
{
    /**
     * @return array<string>
     */
    public function getTargetTables(): array;

    public function resolve(Demands $demands, Record $record): void;
}
