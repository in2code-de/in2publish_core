<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Table;

use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;

class BackupCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const ARG_TABLE_NAME = 'tableName';
    public const ARG_TABLE_NAME_DESCRIPTION = 'The table to back up';
    public const IDENTIFIER = 'in2publish_core:table:backup';

    /** @var Connection */
    protected $localDatabase;

    public function __construct(Connection $localDatabase, string $name = null)
    {
        parent::__construct($name);
        $this->localDatabase = $localDatabase;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, self::ARG_TABLE_NAME_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tableName = $input->getArgument(self::ARG_TABLE_NAME);
        $this->logger->notice('Called Backup Table Command for table "' . $tableName . '"');
        DatabaseUtility::backupTable($this->localDatabase, $tableName);
        return Command::SUCCESS;
    }
}
