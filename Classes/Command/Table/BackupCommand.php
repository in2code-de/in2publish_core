<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\Table;

use In2code\In2publishCore\Utility\DatabaseUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackupCommand extends Command
{
    public const ARG_TABLE_NAME = 'tableName';
    public const ARG_TABLE_NAME_DESCRIPTION = 'The table to back up';
    public const DESCRIPTION = 'Stores a backup of the complete local table into the configured directory';
    public const IDENTIFIER = 'in2publish_core:table:backup';

    protected function configure()
    {
        $this->setDescription(self::DESCRIPTION)
             ->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, self::ARG_TABLE_NAME_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tableName = $input->getArgument(self::ARG_TABLE_NAME);
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $logger->notice('Called Backup Table Command for table "' . $tableName . '"');
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        DatabaseUtility::backupTable($localDatabase, $tableName);
    }
}
