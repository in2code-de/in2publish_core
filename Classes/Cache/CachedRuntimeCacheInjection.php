<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Cache;

/**
 * @codeCoverageIgnore
 */
trait CachedRuntimeCacheInjection
{
    protected CachedRuntimeCache $cachedRuntimeCache;

    /**
     * @noinspection PhpUnused
     */
    public function injectCachedRuntimeCache(CachedRuntimeCache $cachedRuntimeCache): void
    {
        $this->cachedRuntimeCache = $cachedRuntimeCache;
    }
}
