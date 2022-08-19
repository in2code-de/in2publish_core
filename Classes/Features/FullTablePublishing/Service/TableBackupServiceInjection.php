<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FullTablePublishing\Service;

/**
 * @codeCoverageIgnore
 */
trait TableBackupServiceInjection
{
    private TableBackupService $tableBackupService;

    /**
     * @noinspection PhpUnused
     */
    public function injectTableBackupService(TableBackupService $tableBackupService): void
    {
        $this->tableBackupService = $tableBackupService;
    }
}
