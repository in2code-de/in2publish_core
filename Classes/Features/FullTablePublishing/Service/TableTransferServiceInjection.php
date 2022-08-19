<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FullTablePublishing\Service;

/**
 * @codeCoverageIgnore
 */
trait TableTransferServiceInjection
{
    protected TableTransferService $tableTransferService;

    /**
     * @noinspection PhpUnused
     */
    public function injectTableTransferService(TableTransferService $tableTransferService): void
    {
        $this->tableTransferService = $tableTransferService;
    }
}
