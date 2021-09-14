<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Table;

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Service\Database\DatabaseSchemaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_filter;
use function sprintf;

class PublishCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const ARG_TABLE_NAME = 'tableName';
    public const ARG_TABLE_NAME_DESCRIPTION = 'The table to publish';
    public const EXIT_INVALID_TABLE = 220;
    public const EXIT_REMOTE_BACKUP_FAILED = 221;
    public const IDENTIFIER = 'in2publish_core:table:publish';

    /** @var Connection */
    protected $localDatabase;

    /** @var Connection */
    private $foreignDatabase;

    /** @var ContextService */
    private $contextService;

    /** @var DatabaseSchemaService */
    private $databaseSchemaService;

    /** @var RemoteCommandDispatcher */
    private $remoteCommandDispatcher;

    public function __construct(
        Connection $localDatabase,
        Connection $foreignDatabase,
        ContextService $contextService,
        DatabaseSchemaService $databaseSchemaService,
        RemoteCommandDispatcher $remoteCommandDispatcher,
        string $name = null
    ) {
        parent::__construct($name);
        $this->localDatabase = $localDatabase;
        $this->foreignDatabase = $foreignDatabase;
        $this->contextService = $contextService;
        $this->databaseSchemaService = $databaseSchemaService;
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }

    public function configure(): void
    {
        $this->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, self::ARG_TABLE_NAME_DESCRIPTION);
    }

    public function isEnabled(): bool
    {
        return $this->contextService->isLocal();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $tableName = $input->getArgument(self::ARG_TABLE_NAME);

        if (!$this->databaseSchemaService->tableExists($tableName)) {
            $errOutput->writeln(sprintf('The table "%s" does not exist', $tableName));
            $this->logger->error(
                'The table that should be backed up before publish does not exist',
                ['table' => $tableName]
            );
            return static::EXIT_INVALID_TABLE;
        }

        $request = GeneralUtility::makeInstance(
            RemoteCommandRequest::class,
            BackupCommand::IDENTIFIER,
            ['--table-name' => $tableName]
        );
        $response = $this->remoteCommandDispatcher->dispatch($request);

        if ($response->isSuccessful()) {
            $this->logger->info('Backup seems to be successful.');

            try {
                $rowCount = DatabaseUtility::copyTableContents(
                    $this->foreignDatabase,
                    $this->localDatabase,
                    $tableName
                );
                $this->logger->notice('Successfully truncated table, importing rows', ['rowCount' => $rowCount]);
                $this->logger->notice('Finished importing of table', ['table' => $tableName]);
            } catch (In2publishCoreException $exception) {
                $this->logger->critical(
                    'Could not truncate foreign table. Skipping publish',
                    ['table' => $tableName, 'exception' => $exception]
                );
                $errOutput->writeln(sprintf('Could not truncate foreign table "%s". Skipping import', $tableName));
            }
        } else {
            $outputString = $response->getOutputString();
            $errorsString = $response->getErrorsString();
            $exitStatus = $response->getExitStatus();
            $this->logger->error(
                'Could not create backup on remote:',
                ['errors' => $errorsString, 'exit_status' => $exitStatus, 'output' => $outputString]
            );
            $errOutput->writeln(array_filter(['Could not create backup on remote:', $outputString, $errorsString]));
            return static::EXIT_REMOTE_BACKUP_FAILED;
        }

        return Command::SUCCESS;
    }
}
