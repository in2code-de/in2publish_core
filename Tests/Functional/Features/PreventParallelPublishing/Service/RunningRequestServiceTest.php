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

class RunningRequestServiceTest extends FunctionalTestCase
{
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

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');

        $repo = new RunningRequestRepository();
        $repo->injectLocalDatabase($connection);
        $service = new RunningRequestService();
        $service->injectRunningRequestRepository($repo);

        $event = new RecursiveRecordPublishingBegan($recordTree);

        $service->onRecursiveRecordPublishingBegan($event);

        $query = $connection->createQueryBuilder();
        $query->select('*')
              ->from('tx_in2publishcore_running_request')
              ->where(
                  $query->expr()->orX(
                      $query->expr()->andX(
                          $query->expr()->eq('record_id', 1),
                          $query->expr()->eq('table_name', $query->createNamedParameter('foo')),
                      ),
                      $query->expr()->andX(
                          $query->expr()->eq('record_id', $query->createNamedParameter($mmId)),
                          $query->expr()->eq('table_name', $query->createNamedParameter('foo_bar_mm')),
                      ),
                  ),
              );
        $result = $query->execute();
        $rows = $result->fetchAllAssociative();
        $this->assertCount(2, $rows);
    }
}
