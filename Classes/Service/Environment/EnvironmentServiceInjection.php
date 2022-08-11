<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Environment;

/**
 * @codeCoverageIgnore
 */
trait EnvironmentServiceInjection
{
    protected EnvironmentService $environmentService;

    /**
     * @noinspection PhpUnused
     */
    public function injectEnvironmentService(EnvironmentService $environmentService): void
    {
        $this->environmentService = $environmentService;
    }
}
