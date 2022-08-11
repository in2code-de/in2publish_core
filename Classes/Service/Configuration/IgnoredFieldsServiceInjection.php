<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Configuration;

/**
 * @codeCoverageIgnore
 */
trait IgnoredFieldsServiceInjection
{
    protected IgnoredFieldsService $ignoredFieldsService;

    /**
     * @noinspection PhpUnused
     */
    public function injectIgnoredFieldsService(IgnoredFieldsService $ignoredFieldsService): void
    {
        $this->ignoredFieldsService = $ignoredFieldsService;
    }
}
