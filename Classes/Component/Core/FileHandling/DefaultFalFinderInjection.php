<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling;

/**
 * @codeCoverageIgnore
 */
trait DefaultFalFinderInjection
{
    protected DefaultFalFinder $defaultFalFinder;

    /**
     * @noinspection PhpUnused
     */
    public function injectDefaultFalFinder(DefaultFalFinder $defaultFalFinder): void
    {
        $this->defaultFalFinder = $defaultFalFinder;
    }
}
