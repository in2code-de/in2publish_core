<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing;

/**
 * @codeCoverageIgnore
 */
trait CachedTcaPreProcessingServiceInjection
{
    protected CachedTcaPreprocessingService $cachedTcaPreProcessingService;

    /**
     * @noinspection PhpUnused
     */
    public function injectCachedTcaPreprocessingService(
        CachedTcaPreprocessingService $cachedTcaPreprocessingService
    ): void {
        $this->cachedTcaPreProcessingService = $cachedTcaPreprocessingService;
    }
}
