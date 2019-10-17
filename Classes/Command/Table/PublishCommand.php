<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\Table;

use In2code\In2publishCore\Command\TableCommandController;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Service\Database\DatabaseSchemaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_filter;
use function xdebug_break;

class PublishCommand extends Command
{
    protected const ARG_TABLE_NAME = 'tableName';
    public const IDENTIFIER = 'in2publish_core:table:publish';
    public const EXIT_INVALID_TABLE = 220;
    public const EXIT_REMOTE_BACKUP_FAILED = 221;

    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var ContextService
     */
    protected $contextService = null;

    /**
     * @var Connection
     */
    protected $localDatabase;

    /**
     * @var Connection
     */
    protected $foreignDatabase;

    /**
     * @var DatabaseSchemaService
     */
    protected $dbSchemaService;

    public function configure()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->contextService = GeneralUtility::makeInstance(ContextService::class);
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        if ($this->contextService->isLocal()) {
            $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        }
        $this->dbSchemaService = GeneralUtility::makeInstance(DatabaseSchemaService::class);

        $this->setDescription('Copies a complete table from stage to production and overwrites all old entries!')
             ->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, 'The table to publish');
    }

    public function isEnabled()
    {
        return $this->contextService->isLocal();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        xdebug_break();
        $tableName = $input->getArgument(self::ARG_TABLE_NAME);

        if (!$this->dbSchemaService->tableExists($tableName)) {
            $output->writeln('The table does not exist');
            return static::EXIT_INVALID_TABLE;
        }

        $request = GeneralUtility::makeInstance(
            RemoteCommandRequest::class,
            BackupCommand::IDENTIFIER,
            ['--table-name' => $tableName]
        );
        $response = GeneralUtility::makeInstance(RemoteCommandDispatcher::class)->dispatch($request);

        if ($response->isSuccessful()) {
            $this->logger->info('Backup seems to be successful.');

            try {
                $rows = DatabaseUtility::copyTableContents($this->foreignDatabase, $this->localDatabase, $tableName);
                $this->logger->notice('Successfully truncated table, importing ' . $rows . ' rows');
                $this->logger->notice('Finished publishing of table "' . $tableName . '"');
            } catch (In2publishCoreException $exception) {
                $this->logger->critical(
                    'Could not truncate foreign table "' . $tableName . '". Skipping import',
                    ['exception' => $exception]
                );
            }
        } else {
            $this->logger->error(
                'Could not create backup on remote:',
                [
                    'errors' => $response->getErrorsString(),
                    'exit_status' => $response->getExitStatus(),
                    'output' => $response->getOutputString(),
                ]
            );
            $message = array_filter(
                [
                    'Could not create backup on remote:',
                    $response->getOutputString(),
                    $response->getErrorsString(),
                ]
            );
            foreach ($message as $line) {
                $output->writeln($line);
            }

            return static::EXIT_REMOTE_BACKUP_FAILED;
        }
    }
}
