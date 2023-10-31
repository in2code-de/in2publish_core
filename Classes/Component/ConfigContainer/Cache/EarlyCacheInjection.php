<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Cache;

use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;

/**
 * @codeCoverageIgnore
 */
trait EarlyCacheInjection
{
    protected PhpFrontend $earlyCache;

    /**
     * @noinspection PhpUnused
     */
    public function injectEarlyCache(PhpFrontend $earlyCache): void
    {
        $this->earlyCache = $earlyCache;
    }
}
