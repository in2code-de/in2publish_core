<?php


namespace In2code\In2publishCore\Domain\Repository;


use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RunningRequest;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;

class RunningRequestRepository
{
    public const RUNNING_REQUEST_TABLE_NAME = 'tx_in2publishcore_running_request';

    /**
     * @var Connection|null
     */
    protected $connection = null;


    public function __construct()
    {
        $this->connection = DatabaseUtility::buildLocalDatabaseConnection();
    }

    public function add(RunningRequest $runningRequest): void
    {
        $this->connection->insert(
            self::RUNNING_REQUEST_TABLE_NAME,
            $this->mapProperties($runningRequest)
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function existsRunningRequestForRecord(int $identifier, string $tableName): bool
    {
        $statement = $this->connection->select(
            ['uid'],
            self::RUNNING_REQUEST_TABLE_NAME,
            [
                'record_id' => $identifier,
                'table_name' => $tableName
            ]
        );
        return $statement->rowCount() > 0;
    }

    public function deleteAllByRecordTableAndRecordIdentifier(string $table, int $identifier): void
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete(self::RUNNING_REQUEST_TABLE_NAME)
            ->where($query->expr()->eq('table_name', $query->createNamedParameter($table)))
            ->andWhere($query->expr()->eq('record_id', $query->createNamedParameter($identifier)))
            ->execute();
    }

    protected function mapProperties(RunningRequest $runningRequest): array
    {
        return [
            'record_id' => $runningRequest->getRecordId(),
            'table_name' => $runningRequest->getTableName(),
            'request_token' => $runningRequest->getRequestToken(),
            'timestamp_begin' => $runningRequest->getTimestampBegin()
        ];
    }
}
