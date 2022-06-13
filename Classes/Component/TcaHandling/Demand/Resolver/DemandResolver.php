<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Demand\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;

interface DemandResolver
{
    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void;
}
