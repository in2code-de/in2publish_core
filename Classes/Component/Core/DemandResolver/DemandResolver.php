<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\RecordCollection;

interface DemandResolver
{
    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void;
}
