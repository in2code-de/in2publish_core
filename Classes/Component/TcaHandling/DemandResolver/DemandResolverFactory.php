<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\DemandResolver;

class DemandResolverFactory
{
    protected DemandResolverCollection $demandResolverCollection;

    public function __construct(DemandResolverCollection $demandResolverCollection)
    {
        $this->demandResolverCollection = $demandResolverCollection;
    }

    public function addDemandResolver(DemandResolver $demandResolver): void
    {
        $this->demandResolverCollection->addDemandResolver($demandResolver);
    }

    public function createDemandResolver(): DemandResolver
    {
        return $this->demandResolverCollection;
    }
}
