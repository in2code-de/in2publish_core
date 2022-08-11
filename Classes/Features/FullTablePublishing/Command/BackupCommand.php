<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FullTablePublishing\Command;

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Features\FullTablePublishing\Service\TableBackupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BackupCommand extends Command
{
    use LocalDatabaseInjection;

    public const ARG_TABLE_NAME = 'tableName';
    public const ARG_TABLE_NAME_DESCRIPTION = 'The table to back up';
    public const IDENTIFIER = 'in2publish_core:fulltablepublishing:backup';
    private TableBackupService $tableBackupService;

    public function injectTableBackupService(TableBackupService $tableBackupService): void
    {
        $this->tableBackupService = $tableBackupService;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, self::ARG_TABLE_NAME_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tableName = $input->getArgument(self::ARG_TABLE_NAME);
        $this->tableBackupService->createBackup($this->localDatabase, $tableName);
        return Command::SUCCESS;
    }
}
