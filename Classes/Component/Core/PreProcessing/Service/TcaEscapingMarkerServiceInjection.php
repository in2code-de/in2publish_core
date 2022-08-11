<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\Service;

/**
 * @codeCoverageIgnore
 */
trait TcaEscapingMarkerServiceInjection
{
    protected TcaEscapingMarkerService $tcaEscapingMarkerService;

    /**
     * @noinspection PhpUnused
     */
    public function injectTcaEscapingMarkerService(TcaEscapingMarkerService $tcaEscapingMarkerService): void
    {
        $this->tcaEscapingMarkerService = $tcaEscapingMarkerService;
    }
}
