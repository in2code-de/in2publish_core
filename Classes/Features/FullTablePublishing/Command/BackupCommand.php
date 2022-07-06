<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FullTablePublishing\Command;

use In2code\In2publishCore\Features\FullTablePublishing\Service\TableBackupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;

class BackupCommand extends Command
{
    public const ARG_TABLE_NAME = 'tableName';
    public const ARG_TABLE_NAME_DESCRIPTION = 'The table to back up';
    public const IDENTIFIER = 'in2publish_core:fulltablepublishing:backup';
    private TableBackupService $tableBackupService;
    private Connection $localDatabase;

    public function injectTableBackupService(TableBackupService $tableBackupService): void
    {
        $this->tableBackupService = $tableBackupService;
    }

    public function injectConnection(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
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
