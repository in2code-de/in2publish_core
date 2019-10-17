<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\Table;

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

class BackupCommand extends Command
{
    protected const ARG_TABLE_NAME = 'tableName';
    public const IDENTIFIER = 'in2publish_core:table:backup';

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
        $this->setDescription('Stores a backup of the complete local table into the configured directory')
             ->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, 'The table to back up');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->contextService = GeneralUtility::makeInstance(ContextService::class);
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        if ($this->contextService->isLocal()) {
            $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        }
        $this->dbSchemaService = GeneralUtility::makeInstance(DatabaseSchemaService::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tableName = $input->getArgument(self::ARG_TABLE_NAME);
        $this->logger->notice('Called Backup Table Command for table "' . $tableName . '"');
        DatabaseUtility::backupTable($this->localDatabase, $tableName);
    }
}
