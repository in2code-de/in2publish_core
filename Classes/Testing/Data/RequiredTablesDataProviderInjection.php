<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Data;

/**
 * @codeCoverageIgnore
 */
trait RequiredTablesDataProviderInjection
{
    protected RequiredTablesDataProvider $requiredTablesDataProvider;

    /**
     * @noinspection PhpUnused
     */
    public function injectRequiredTablesDataProvider(RequiredTablesDataProvider $requiredTablesDataProvider): void
    {
        $this->requiredTablesDataProvider = $requiredTablesDataProvider;
    }
}
