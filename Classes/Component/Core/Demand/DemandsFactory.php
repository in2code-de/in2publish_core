<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;

class DemandsFactory
{
    use ConfigContainerInjection;

    public function createDemand(): Demands
    {
        $demand = new DemandsCollection();
        if ($this->configContainer->get('debug.traceDemand')) {
            $demand = new CallerAwareDemandsCollection($demand);
        }
        return $demand;
    }
}
