<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

/**
 * @codeCoverageIgnore
 */
trait SharedFilesystemInfoCacheInjection
{
    protected SharedFilesystemInfoCache $sharedFilesystemInfoCache;

    /**
     * @noinspection PhpUnused
     */
    public function injectSharedFilesystemInfoCache(SharedFilesystemInfoCache $sharedFilesystemInfoCache): void
    {
        $this->sharedFilesystemInfoCache = $sharedFilesystemInfoCache;
    }
}
