<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SkipEmptyTable;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_column;
use function array_keys;

class SkipTableByPidVoter implements SingletonInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var null|array */
    protected $pidIndex = null;

    protected $statistics = [
        'skippedQueries' => 0,
        'executedQueries' => 0,
    ];

    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
    }

    public function __destruct()
    {
        $this->logger->debug('SkipTableByPidVoter statistics', $this->statistics);
    }

    public function shouldSkipSearchingForRelatedRecordByTable(
        array $votes,
        CommonRepository $repository,
        array $arguments
    ): array {
        $this->initialize();
        /** @var RecordInterface $record */
        $record = $arguments['record'];
        /** @var string $tableName */
        $tableName = $arguments['tableName'];
        if (!isset($this->pidIndex[$tableName][$record->getIdentifier()])) {
            $this->statistics['skippedQueries']++;
            $votes['yes']++;
        }
        return [$votes, $repository, $arguments];
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function initialize(): void
    {
        if (null !== $this->pidIndex) {
            return;
        }

        $localConnection = DatabaseUtility::buildLocalDatabaseConnection();
        $foreignConnection = DatabaseUtility::buildForeignDatabaseConnection();

        $tables = array_keys($GLOBALS['TCA']);
        foreach ($tables as $table) {
            $this->pidIndex[$table] = [];
            /** @var Connection $connection */
            foreach ([$localConnection, $foreignConnection] as $connection) {
                $pids = $connection
                    ->executeQuery('SELECT DISTINCT pid FROM ' . $connection->quoteIdentifier($table))
                    ->fetchAll();
                $this->statistics['executedQueries']++;
                foreach (array_column($pids, 'pid') as $pid) {
                    $this->pidIndex[$table][$pid] = $pid;
                }
            }
        }
    }
}
