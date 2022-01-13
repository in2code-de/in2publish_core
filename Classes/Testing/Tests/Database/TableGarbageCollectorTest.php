<?php


namespace In2code\In2publishCore\Testing\Tests\Database;


use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;

class TableGarbageCollectorTest implements TestCaseInterface
{
    public function run(): TestResult
    {
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();

        if (!($localDatabase instanceof Connection)) {
            return new TestResult('database.local_inaccessible', TestResult::ERROR);
        }

        if (!$localDatabase->isConnected()) {
            return new TestResult('database.local_offline', TestResult::ERROR);
        }

        $query = $localDatabase->createQueryBuilder();
        $statement = $query->select('*')
            ->from('tx_scheduler_task')
            ->where($query->expr()->like(
                'serialized_task_object',
                $query->createNamedParameter('%tx_in2publishcore_running_request%'))
            )
            ->execute();
        $garbageCollectionTasksExists = $statement->rowCount() > 0;

        if ($garbageCollectionTasksExists == false) {
            return new TestResult(
                'database.garbage_collector_task_missing',
                TestResult::ERROR
            );
        }

        return new TestResult('database.garbage_collector_task_exists');
    }

    public function getDependencies(): array
    {
        return [];
    }
}
