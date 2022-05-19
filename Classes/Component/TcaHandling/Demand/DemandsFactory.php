<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Demand;

use In2code\In2publishCore\Config\ConfigContainer;

class DemandsFactory
{
    protected ConfigContainer $configContainer;

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
    }

    public function buildDemand(): Demands
    {
        $demand = new DemandsCollection();
        if ($this->configContainer->get('debug.traceDemand')) {
            $demand = new CallerAwareDemandsCollection($demand);
        }
        return $demand;
    }
}
