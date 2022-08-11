<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service;

/**
 * @codeCoverageIgnore
 */
trait ForeignSiteFinderInjection
{
    protected ForeignSiteFinder $foreignSiteFinder;

    /**
     * @noinspection PhpUnused
     */
    public function injectForeignSiteFinder(ForeignSiteFinder $foreignSiteFinder): void
    {
        $this->foreignSiteFinder = $foreignSiteFinder;
    }
}
