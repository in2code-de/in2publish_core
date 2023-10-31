<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\Demand\Remover\DemandRemover;
use In2code\In2publishCore\Component\Core\Demand\Type\Demand;

use function get_class;

class DemandsCollection implements Demands
{
    use DeprecatedDemandsCollection;

    private array $demands = [];

    public function addDemand(Demand $demand): void
    {
        $class = get_class($demand);
        $this->demands[$class] ??= [];
        $demands = &$this->demands[$class];
        $demand->addToDemandsArray($demands);
    }

    public function unsetDemand(DemandRemover $demandRemover): void
    {
        $class = $demandRemover->getDemandType();
        $this->demands[$class] ??= [];
        $demands = &$this->demands[$class];
        $demandRemover->removeFromDemandsArray($demands);
    }

    public function getDemandsByType(string $type): array
    {
        return $this->demands[$type] ?? [];
    }
}
