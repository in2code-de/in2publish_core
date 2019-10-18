<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\Table;

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Service\Database\DatabaseSchemaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_filter;
use function sprintf;

class PublishCommand extends Command
{
    public const ARG_TABLE_NAME = 'tableName';
    public const ARG_TABLE_NAME_DESCRIPTION = 'The table to publish';
    public const DESCRIPTION = 'Copies a complete table from stage to production and overwrites all old entries!';
    public const EXIT_INVALID_TABLE = 220;
    public const EXIT_REMOTE_BACKUP_FAILED = 221;
    public const IDENTIFIER = 'in2publish_core:table:publish';

    public function configure()
    {
        $this->setDescription(self::DESCRIPTION)
             ->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, self::ARG_TABLE_NAME_DESCRIPTION);
    }

    public function isEnabled()
    {
        return GeneralUtility::makeInstance(ContextService::class)->isLocal();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();

        $tableName = $input->getArgument(self::ARG_TABLE_NAME);

        $dbSchemaService = GeneralUtility::makeInstance(DatabaseSchemaService::class);
        if (!$dbSchemaService->tableExists($tableName)) {
            $errOutput->writeln(sprintf('The table "%s" does not exist', $tableName));
            $logger->error('The table that should be backed up before publish does not exist', ['table' => $tableName]);
            return static::EXIT_INVALID_TABLE;
        }

        $request = GeneralUtility::makeInstance(
            RemoteCommandRequest::class,
            BackupCommand::IDENTIFIER,
            ['--table-name' => $tableName]
        );
        $response = GeneralUtility::makeInstance(RemoteCommandDispatcher::class)->dispatch($request);

        if ($response->isSuccessful()) {
            $logger->info('Backup seems to be successful.');

            try {
                $rowCount = DatabaseUtility::copyTableContents($foreignDatabase, $localDatabase, $tableName);
                $logger->notice('Successfully truncated table, importing rows', ['rowCount' => $rowCount]);
                $logger->notice('Finished importing of table', ['table' => $tableName]);
            } catch (In2publishCoreException $exception) {
                $logger->critical(
                    'Could not truncate foreign table. Skipping publish',
                    ['table' => $tableName, 'exception' => $exception]
                );
                $errOutput->writeln(sprintf('Could not truncate foreign table "%s". Skipping import', $tableName));
            }
        } else {
            $outputString = $response->getOutputString();
            $errorsString = $response->getErrorsString();
            $exitStatus = $response->getExitStatus();
            $logger->error(
                'Could not create backup on remote:',
                ['errors' => $errorsString, 'exit_status' => $exitStatus, 'output' => $outputString]
            );
            $errOutput->writeln(array_filter(['Could not create backup on remote:', $outputString, $errorsString]));
            return static::EXIT_REMOTE_BACKUP_FAILED;
        }

        return 0;
    }
}
