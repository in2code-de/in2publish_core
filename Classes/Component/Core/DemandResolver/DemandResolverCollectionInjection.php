<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver;

/**
 * @codeCoverageIgnore
 */
trait DemandResolverCollectionInjection
{
    protected DemandResolverCollection $demandResolverCollection;

    /**
     * @noinspection PhpUnused
     */
    public function injectDemandResolverCollection(DemandResolverCollection $demandResolverCollection): void
    {
        $this->demandResolverCollection = $demandResolverCollection;
    }
}
