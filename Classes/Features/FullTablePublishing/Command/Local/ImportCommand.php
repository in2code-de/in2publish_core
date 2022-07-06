<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FullTablePublishing\Command\Local;

use In2code\In2publishCore\Features\FullTablePublishing\Service\TableBackupService;
use In2code\In2publishCore\Features\FullTablePublishing\Service\TableTransferService;
use In2code\In2publishCore\Service\Context\ContextService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;

class ImportCommand extends Command
{
    public const ARG_TABLE = 'table';
    public const ARG_TABLE_DESCRIPTION = 'The table which should be truncated and filled with data from the foreign database.';
    public const IDENTIFIER = 'in2publish_core:fulltablepublishing:import';
    protected Connection $localDatabase;
    private Connection $foreignDatabase;
    private ContextService $contextService;
    private TableBackupService $tableBackupService;
    private TableTransferService $tableTransferService;

    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }

    public function injectForeignDatabase(Connection $foreignDatabase): void
    {
        $this->foreignDatabase = $foreignDatabase;
    }

    public function injectContextService(ContextService $contextService): void
    {
        $this->contextService = $contextService;
    }

    public function injectTableBackupService(TableBackupService $tableBackupService): void
    {
        $this->tableBackupService = $tableBackupService;
    }

    public function injectTableTransferService(TableTransferService $tableTransferService): void
    {
        $this->tableTransferService = $tableTransferService;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_TABLE, InputArgument::REQUIRED, self::ARG_TABLE_DESCRIPTION);
    }

    public function isEnabled(): bool
    {
        return $this->contextService->isLocal();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = $input->getArgument(self::ARG_TABLE);

        $this->tableBackupService->createBackup($this->localDatabase, $table);
        $this->tableTransferService->copyTableContents($this->foreignDatabase, $this->localDatabase, $table);

        return Command::SUCCESS;
    }
}
