<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

/**
 * @codeCoverageIgnore
 */
trait FalDriverServiceInjection
{
    protected FalDriverService $falDriverService;

    /**
     * @noinspection PhpUnused
     */
    public function injectFalDriverService(FalDriverService $falDriverService): void
    {
        $this->falDriverService = $falDriverService;
    }
}
