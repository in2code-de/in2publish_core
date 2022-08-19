<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver;

class DemandResolverFactory
{
    protected DemandResolverCollection $demandResolverCollection;

    /**
     * Must be constructor injection to be available before "addDemandResolver"
     */
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
