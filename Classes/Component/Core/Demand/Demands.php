<?php

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\Demand\Remover\DemandRemover;
use In2code\In2publishCore\Component\Core\Demand\Type\Demand;

interface Demands extends DeprecatedDemands
{
    public function addDemand(Demand $demand): void;

    public function unsetDemand(DemandRemover $demandRemover): void;

    public function getDemandsByType(string $type): array;
}
