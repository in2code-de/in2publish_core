<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Table;

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

use function sprintf;

class ImportCommand extends Command
{
    public const ARG_TABLE_NAME = 'tableName';
    public const ARG_TABLE_NAME_DESCRIPTION = 'The table to back up';
    public const DESCRIPTION = 'Stores a backup of the complete local table into the configured directory';
    public const EXIT_INVALID_TABLE = 220;
    public const IDENTIFIER = 'in2publish_core:table:import';

    protected function configure()
    {
        $this->setDescription(self::DESCRIPTION)
             ->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, self::ARG_TABLE_NAME_DESCRIPTION);
    }

    public function isEnabled()
    {
        return GeneralUtility::makeInstance(ContextService::class)->isLocal();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $tableName = $input->getArgument(self::ARG_TABLE_NAME);

        $dbSchemaService = GeneralUtility::makeInstance(DatabaseSchemaService::class);
        if (!$dbSchemaService->tableExists($tableName)) {
            $errOutput->writeln(sprintf('The table "%s" does not exist', $tableName));
            $logger->error('The table that should be backed up before import does not exist', ['table' => $tableName]);
            return self::EXIT_INVALID_TABLE;
        }

        $logger->notice('Called Import Table Command for table', ['table' => $tableName]);

        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        DatabaseUtility::backupTable($localDatabase, $tableName);

        try {
            $foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
            $rowCount = DatabaseUtility::copyTableContents($foreignDatabase, $localDatabase, $tableName);
            $logger->notice('Successfully truncated table, importing rows', ['rowCount' => $rowCount]);
            $logger->notice('Finished importing of table', ['table' => $tableName]);
        } catch (In2publishCoreException $exception) {
            $logger->critical(
                'Could not truncate local table. Skipping import',
                ['table' => $tableName, 'exception' => $exception]
            );
            $errOutput->writeln(sprintf('Could not truncate local table "%s". Skipping import', $tableName));
        }

        return 0;
    }
}
