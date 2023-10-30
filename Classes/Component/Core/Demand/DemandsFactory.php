<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;

class DemandsFactory
{
    use ConfigContainerInjection;

    public function createDemand(): Demands
    {
        if ($this->configContainer->get('debug.traceDemand')) {
            return new CallerAwareDemandsCollection();
        }
        return new DemandsCollection();
    }
}
