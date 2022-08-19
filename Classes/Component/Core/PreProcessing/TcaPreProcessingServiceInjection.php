<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing;

/**
 * @codeCoverageIgnore
 */
trait TcaPreProcessingServiceInjection
{
    protected TcaPreProcessingService $tcaPreProcessingService;

    /**
     * @noinspection PhpUnused
     */
    public function injectTcaPreProcessingService(TcaPreProcessingService $tcaPreProcessingService): void
    {
        $this->tcaPreProcessingService = $tcaPreProcessingService;
    }
}
