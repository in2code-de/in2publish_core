<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver;

use In2code\In2publishCore\CommonInjection\EventDispatcherInjection;
use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Event\DemandsWereCollected;
use In2code\In2publishCore\Event\DemandsWereResolved;

class DemandResolverCollection implements DemandResolver
{
    use EventDispatcherInjection;

    /**
     * @var array<DemandResolver>
     */
    private array $demandResolvers = [];

    public function addDemandResolver(DemandResolver $demandResolver): void
    {
        $this->demandResolvers[] = $demandResolver;
    }

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        $this->eventDispatcher->dispatch(new DemandsWereCollected($demands));
        foreach ($this->demandResolvers as $demandResolver) {
            $demandResolver->resolveDemand($demands, $recordCollection);
        }
        $this->eventDispatcher->dispatch(new DemandsWereResolved($demands, $recordCollection));
    }
}
