<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Backend\Routing\UriBuilder;

/**
 * @codeCoverageIgnore
 */
trait UriBuilderInjection
{
    private UriBuilder $uriBuilder;

    /**
     * @noinspection PhpUnused
     */
    public function injectUriBuilder(UriBuilder $uriBuilder): void
    {
        $this->uriBuilder = $uriBuilder;
    }
}
