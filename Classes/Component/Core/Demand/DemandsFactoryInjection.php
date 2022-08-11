<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

/**
 * @codeCoverageIgnore
 */
trait DemandsFactoryInjection
{
    private DemandsFactory $demandsFactory;

    /**
     * @noinspection PhpUnused
     */
    public function injectDemandsFactory(DemandsFactory $demandsFactory): void
    {
        $this->demandsFactory = $demandsFactory;
    }
}
