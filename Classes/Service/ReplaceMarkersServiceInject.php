<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service;

/**
 * @codeCoverageIgnore
 */
trait ReplaceMarkersServiceInject
{
    protected ReplaceMarkersService $replaceMarkersService;

    /**
     * @noinspection PhpUnused
     */
    public function injectReplaceMarkersService(ReplaceMarkersService $replaceMarkersService): void
    {
        $this->replaceMarkersService = $replaceMarkersService;
    }
}
