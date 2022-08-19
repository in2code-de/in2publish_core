<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

/**
 * @codeCoverageIgnore
 */
trait EventDispatcherInjection
{
    protected EventDispatcher $eventDispatcher;

    /**
     * @noinspection PhpUnused
     */
    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
