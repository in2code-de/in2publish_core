<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\Service\ResolverService;

/**
 * @codeCoverageIgnore
 */
trait ResolverServiceInjection
{
    protected ResolverService $resolverService;

    /**
     * @noinspection PhpUnused
     */
    public function injectResolverService(ResolverService $resolverService): void
    {
        $this->resolverService = $resolverService;
    }
}
