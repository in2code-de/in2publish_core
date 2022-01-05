<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\RecordHandling;

use In2code\In2publishCore\Component\RecordHandling\DefaultRecordFinder;
use In2code\In2publishCore\Component\RecordHandling\DefaultRecordPublisher;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RecordPublishingTest extends FunctionalTestCase
{
    public function testRelatedRecordsArePublished(): void
    {
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $defaultConnection = $pool->getConnectionByName('Default');
        $foreignConnection = $pool->getConnectionByName('Foreign');
        $defaultConnection->insert('pages', ['uid' => 5, 'sys_language_uid' => 1]);
        $defaultConnection->insert('sys_language', ['uid' => 1]);

        $query = $foreignConnection->createQueryBuilder();
        $query->select('*')->from('sys_language');
        $result = $query->execute();
        $rows = $result->fetchAllAssociative();
        $this->assertEmpty($rows);

        $defaultRecordFinder = GeneralUtility::makeInstance(DefaultRecordFinder::class);
        $record = $defaultRecordFinder->findByIdentifier(5, 'pages');

        $defaultRecordPublisher = GeneralUtility::makeInstance(DefaultRecordPublisher::class);
        $defaultRecordPublisher->publishRecordRecursive($record);

        $query = $foreignConnection->createQueryBuilder();
        $query->select('*')->from('sys_language');
        $result = $query->execute();
        $rows = $result->fetchAllAssociative();

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]['uid']);
    }
}
