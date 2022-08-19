<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver;

/**
 * @codeCoverageIgnore
 */
trait DemandResolverInjection
{
    private DemandResolver $demandResolver;

    /**
     * @noinspection PhpUnused
     */
    public function injectDemandResolver(DemandResolver $demandResolver): void
    {
        $this->demandResolver = $demandResolver;
    }
}
