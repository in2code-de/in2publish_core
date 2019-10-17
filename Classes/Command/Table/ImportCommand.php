<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\Table;

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

class ImportCommand extends Command
{
    protected const ARG_TABLE_NAME = 'tableName';
    public const IDENTIFIER = 'in2publish_core:table:import';
    public const EXIT_INVALID_TABLE = 220;

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

    protected function configure()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->contextService = GeneralUtility::makeInstance(ContextService::class);
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        if ($this->contextService->isLocal()) {
            $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        }
        $this->dbSchemaService = GeneralUtility::makeInstance(DatabaseSchemaService::class);

        $this->setDescription('Stores a backup of the complete local table into the configured directory')
             ->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, 'The table to back up');
    }

    public function isEnabled()
    {
        return $this->contextService->isLocal();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tableName = $input->getArgument(self::ARG_TABLE_NAME);
        if (!$this->dbSchemaService->tableExists($tableName)) {
            $output->writeln('The table does not exist');
            return self::EXIT_INVALID_TABLE;
        }

        $this->logger->notice('Called Import Table Command for table "' . $tableName . '"');
        DatabaseUtility::backupTable($this->localDatabase, $tableName);
        try {
            $rows = DatabaseUtility::copyTableContents($this->foreignDatabase, $this->localDatabase, $tableName);
            $this->logger->notice('Successfully truncated table, importing ' . $rows . ' rows');
            $this->logger->notice('Finished importing of table "' . $tableName . '"');
        } catch (In2publishCoreException $exception) {
            $this->logger->critical(
                'Could not truncate local table "' . $tableName . '". Skipping import',
                ['exception' => $exception]
            );
        }
    }
}
