<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Configuration;

/**
 * @codeCoverageIgnore
 */
trait TcaServiceInjection
{
    protected TcaService $tcaService;

    /**
     * @noinspection PhpUnused
     */

    public function injectTcaService(TcaService $tcaService): void
    {
        $this->tcaService = $tcaService;
    }
}
