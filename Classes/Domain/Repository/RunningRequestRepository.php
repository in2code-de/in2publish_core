<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Repository;

use In2code\In2publishCore\Domain\Model\RunningRequest;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;

class RunningRequestRepository
{
    public const RUNNING_REQUEST_TABLE_NAME = 'tx_in2publishcore_running_request';

    /** @var Connection */
    protected $connection;

    protected $inserts = [];

    protected $rtc = [];

    public function __construct()
    {
        $this->connection = DatabaseUtility::buildLocalDatabaseConnection();
    }

    public function add($recordId, $tableName, $token): void
    {
        $uniqueKey = $tableName . '/' . $recordId;
        $this->inserts[$uniqueKey] = [
            'uid' => null,
            'record_id' => $recordId,
            'table_name' => $tableName,
            'request_token' => $token,
            'timestamp_begin' => $GLOBALS['EXEC_TIME'],
        ];
    }

    public function flush(): void
    {
        $this->connection->bulkInsert(self::RUNNING_REQUEST_TABLE_NAME, $this->inserts);
        $this->inserts = [];
    }

    /**
     * @param int|string $identifier
     */
    public function isPublishingInDifferentRequest($identifier, string $tableName, string $token): bool
    {
        if (!isset($this->rtc['content'])) {
            $query = $this->connection->createQueryBuilder();
            $query->select('*')
                ->from(self::RUNNING_REQUEST_TABLE_NAME)
                ->where($query->expr()->neq('request_token', $query->createNamedParameter($token)));
            $result = $query->execute();
            foreach ($result->fetchAll() as $row) {
                $this->rtc['content'][$row['table_name']][$row['record_id']] = true;
            }
        }
        return isset($this->rtc['content'][$tableName][$identifier]);
    }

    public function deleteAllByToken(string $token): void
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete(self::RUNNING_REQUEST_TABLE_NAME)
            ->where($query->expr()->eq('request_token', $query->createNamedParameter($token)))
            ->execute();
    }
}
