<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Service;

/**
 * @codeCoverageIgnore
 */
trait RelevantTablesServiceInjection
{
    protected RelevantTablesService $relevantTablesService;

    /**
     * @noinspection PhpUnused
     */
    public function injectRelevantTablesService(RelevantTablesService $relevantTablesService): void
    {
        $this->relevantTablesService = $relevantTablesService;
    }
}
