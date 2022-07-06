<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\DemandResolver;

use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Event\DemandsWereCollected;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

class DemandResolverCollection implements DemandResolver
{
    protected EventDispatcher $eventDispatcher;
    /**
     * @var array<DemandResolver>
     */
    private array $demandResolvers = [];

    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

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
    }
}
