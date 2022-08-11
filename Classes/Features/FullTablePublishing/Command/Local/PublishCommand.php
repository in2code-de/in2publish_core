<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FullTablePublishing\Command\Local;

use In2code\In2publishCore\CommonInjection\ForeignDatabaseInjection;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Features\FullTablePublishing\Command\BackupCommand;
use In2code\In2publishCore\Features\FullTablePublishing\Service\TableTransferService;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class PublishCommand extends Command
{
    use LocalDatabaseInjection;
    use ForeignDatabaseInjection;
    use ContextServiceInjection;

    public const ARG_TABLE = 'table';
    public const ARG_TABLE_DESCRIPTION = 'The table to write to the foreign database.';
    public const IDENTIFIER = 'in2publish_core:fulltablepublishing:publish';
    private TableTransferService $tableTransferService;
    private RemoteCommandDispatcher $remoteCommandDispatcher;

    public function injectTableTransferService(TableTransferService $tableTransferService): void
    {
        $this->tableTransferService = $tableTransferService;
    }

    public function injectRemoteCommandDispatcher(RemoteCommandDispatcher $remoteCommandDispatcher): void
    {
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
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

        $command = new RemoteCommandRequest(BackupCommand::IDENTIFIER, [], [$table]);
        $response = $this->remoteCommandDispatcher->dispatch($command);
        if (!$response->isSuccessful()) {
            $stdErr = $output instanceof ConsoleOutput ? $output->getErrorOutput() : $output;
            $stdErr->write($response->getOutputString());
            $stdErr->write($response->getErrorsString());
            return Command::FAILURE;
        }
        $this->tableTransferService->copyTableContents($this->localDatabase, $this->foreignDatabase, $table);

        return Command::SUCCESS;
    }
}
