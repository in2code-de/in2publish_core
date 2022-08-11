<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

/**
 * @codeCoverageIgnore
 */
trait DemandBuilderInjection
{
    protected DemandBuilder $demandBuilder;

    /**
     * @noinspection PhpUnused
     */
    public function injectDemandBuilder(DemandBuilder $demandBuilder): void
    {
        $this->demandBuilder = $demandBuilder;
    }
}
