<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Language;

/**
 * @codeCoverageIgnore
 */
trait SiteLanguageServiceInjection
{
    protected SiteLanguageService $siteLanguageService;

    /**
     * @noinspection PhpUnused
     */
    public function injectSiteLanguageService(SiteLanguageService $siteLanguageService): void
    {
        $this->siteLanguageService = $siteLanguageService;
    }
}
