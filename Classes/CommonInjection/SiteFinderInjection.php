<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * @codeCoverageIgnore
 */
trait SiteFinderInjection
{
    protected SiteFinder $siteFinder;

    /**
     * @noinspection PhpUnused
     */
    public function injectSiteFinder(SiteFinder $siteFinder): void
    {
        $this->siteFinder = $siteFinder;
    }
}
