<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FullTablePublishing\Command\Local;

use In2code\In2publishCore\CommonInjection\ForeignDatabaseInjection;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Features\FullTablePublishing\Service\TableBackupServiceInjection;
use In2code\In2publishCore\Features\FullTablePublishing\Service\TableTransferServiceInjection;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    use LocalDatabaseInjection;
    use ForeignDatabaseInjection;
    use ContextServiceInjection;
    use TableBackupServiceInjection;
    use TableTransferServiceInjection;

    public const ARG_TABLE = 'table';
    public const ARG_TABLE_DESCRIPTION = 'The table which should be truncated and filled with data from the foreign database.';
    public const IDENTIFIER = 'in2publish_core:fulltablepublishing:import';

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
