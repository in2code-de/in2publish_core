<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Service\Config;

/**
 * @codeCoverageIgnore
 */
trait ExcludedTablesServiceInjection
{
    protected ExcludedTablesService $excludedTablesService;

    /**
     * @noinspection PhpUnused
     */
    public function injectExcludedTablesService(ExcludedTablesService $excludedTablesService): void
    {
        $this->excludedTablesService = $excludedTablesService;
    }
}
