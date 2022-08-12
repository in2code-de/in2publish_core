<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * @codeCoverageIgnore
 */
trait CacheInjection
{
    protected FrontendInterface $cache;

    /**
     * @noinspection PhpUnused
     */
    public function injectCache(FrontendInterface $cache): void
    {
        $this->cache = $cache;
    }
}
