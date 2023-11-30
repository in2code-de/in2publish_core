<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

/**
 * @codeCoverageIgnore
 */
trait LocalFileInfoServiceInjection
{
    protected LocalFileInfoService $localFileInfoService;

    /**
     * @noinspection PhpUnused
     */
    public function injectLocalFileInfoService(LocalFileInfoService $localFileInfoService): void
    {
        $this->localFileInfoService = $localFileInfoService;
    }
}
