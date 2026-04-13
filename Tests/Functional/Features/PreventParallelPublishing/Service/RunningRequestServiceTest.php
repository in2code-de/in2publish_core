<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Features\PreventParallelPublishing\Service;

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\MmDatabaseRecord;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use In2code\In2publishCore\Features\PreventParallelPublishing\Domain\Repository\RunningRequestRepository;
use In2code\In2publishCore\Features\PreventParallelPublishing\Service\RunningRequestService;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[\PHPUnit\Framework\Attributes\CoversClass(RunningRequestService::class)]
class RunningRequestServiceTest extends FunctionalTestCase
{
    protected ConnectionPool $connectionPool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * @ticket https://projekte.in2code.de/issues/51299
     * @ticket https://projekte.in2code.de/issues/51301
     */
    public function testRecordWithMmRecordCanBeMarkedAsPublishing(): void
    {
        $mmId = '{uid_local: 1, uid_foreign: 2, sorting: 15}';

        $mmRecord = new MmDatabaseRecord(
            'foo_bar_mm',
            $mmId,
            ['uid_local' => 1, 'uid_foreign' => 2, 'sorting' => 15],
            ['uid_local' => 1, 'uid_foreign' => 2, 'sorting' => 15],
        );

        $record = new DatabaseRecord('foo', 1, ['uid' => 1], ['uid' => 1], []);
        $record->addChild($mmRecord);

        $recordTree = new RecordTree([$record]);

        $connectionPool = $this->connectionPool;
        $connection = $connectionPool->getConnectionByName('Default');

        $repo = GeneralUtility::makeInstance(RunningRequestRepository::class);
        $repo->injectLocalDatabase($connection);
        $service = new RunningRequestService($repo);

        $event = new RecursiveRecordPublishingBegan($recordTree);

        $service->onRecursiveRecordPublishingBegan($event);

        $query = $connection->createQueryBuilder();
        $query->select('*')
              ->from('tx_in2publishcore_running_request')
              ->where(
                  '(record_id = :recordId AND table_name = :tableName)'
                  . ' OR '
                  . '(record_id = :mmRecordId AND table_name = :mmTableName)',
              )
              ->setParameter('recordId', 1)
              ->setParameter('tableName', 'foo')
              ->setParameter('mmRecordId', $mmId)
              ->setParameter('mmTableName', 'foo_bar_mm');
        $result = $query->executeQuery();
        $rows = $result->fetchAllAssociative();
        $this->assertCount(2, $rows);
    }
}
