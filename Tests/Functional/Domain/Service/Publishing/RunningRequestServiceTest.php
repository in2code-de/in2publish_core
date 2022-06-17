<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Domain\Service\Publishing;

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Repository\RunningRequestRepository;
use In2code\In2publishCore\Domain\Service\Publishing\RunningRequestService;
use In2code\In2publishCore\Event\RecordsWereSelectedForPublishing;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function json_encode;

class RunningRequestServiceTest extends FunctionalTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51299
     * @ticket https://projekte.in2code.de/issues/51301
     */
    public function testRecordWithMmRecordCanBeMarkedAsPublishing(): void
    {
        $mmId = json_encode(['uid_local' => 1, 'uid_foreign' => 2, 'sorting' => 15]);
        $mmRecord = new Record(
            'foo_bar_mm',
            ['uid_local' => 1, 'uid_foreign' => 2, 'sorting' => 15],
            ['uid_local' => 1, 'uid_foreign' => 2, 'sorting' => 15],
            [],
            [],
            $mmId
        );

        $record = new Record('foo', ['uid' => 1], ['uid' => 1], [], [], 1);
        $record->addRelatedRecord($mmRecord);

        $repo = new RunningRequestRepository();
        $service = new RunningRequestService($repo);

        $event = new RecordsWereSelectedForPublishing([$record]);

        $service->onRecordsWereSelectedForPublishing($event);

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $query = $connection->createQueryBuilder();
        $query->select('*')
              ->from('tx_in2publishcore_running_request')
              ->where(
                  $query->expr()->orX(
                      $query->expr()->andX(
                          $query->expr()->eq('record_id', 1),
                          $query->expr()->eq('table_name', $query->createNamedParameter('foo'))
                      ),
                      $query->expr()->andX(
                          $query->expr()->eq('record_id', $query->createNamedParameter($mmId)),
                          $query->expr()->eq('table_name', $query->createNamedParameter('foo_bar_mm'))
                      )
                  )
              );
        $result = $query->execute();
        $rows = $result->fetchAll();
        $this->assertCount(2, $rows);
    }
}
