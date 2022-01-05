<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\RecordHandling;

use In2code\In2publishCore\Component\FalHandling\PostProcessing\EventListener\PostProcessingEventListener;
use In2code\In2publishCore\Component\RecordHandling\DefaultRecordFinder;
use In2code\In2publishCore\Component\RecordHandling\DefaultRecordPublisher;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Event\RootRecordCreationWasFinished;
use In2code\In2publishCore\Features\PhysicalFilePublisher\Domain\Anomaly\PhysicalFilePublisher;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use ReflectionProperty;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RecordPublishingTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $listenerProvider = GeneralUtility::makeInstance(ListenerProvider::class);
        $reflectionProperty = new ReflectionProperty(ListenerProvider::class, 'listeners');
        $reflectionProperty->setAccessible(true);
        $listener = $reflectionProperty->getValue($listenerProvider);
        foreach ($listener[RootRecordCreationWasFinished::class] as $index => $config) {
            if ($config['service'] === PostProcessingEventListener::class) {
                unset($listener[RootRecordCreationWasFinished::class][$index]);
            }
        }
        foreach ($listener[PublishingOfOneRecordEnded::class] as $index => $config) {
            if ($config['service'] === PhysicalFilePublisher::class) {
                unset($listener[PublishingOfOneRecordEnded::class][$index]);
            }
        }
        $reflectionProperty->setValue($listenerProvider, $listener);
    }

    public function testRelatedRecordsArePublished(): void
    {
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $defaultConnection = $pool->getConnectionByName('Default');
        $foreignConnection = $pool->getConnectionByName('Foreign');

        $defaultConnection->insert('sys_file', ['uid' => 3, 'storage' => 2]);
        $defaultConnection->insert('sys_file_storage', ['uid' => 2]);

        $query = $foreignConnection->createQueryBuilder();
        $query->select('*')->from('sys_file_storage');
        $result = $query->execute();
        $rows = $result->fetchAllAssociative();
        $this->assertEmpty($rows);

        $defaultRecordFinder = GeneralUtility::makeInstance(DefaultRecordFinder::class);
        $record = $defaultRecordFinder->findByIdentifier(3, 'sys_file');

        $defaultRecordPublisher = GeneralUtility::makeInstance(DefaultRecordPublisher::class);
        $defaultRecordPublisher->publishRecordRecursive($record);

        $query = $foreignConnection->createQueryBuilder();
        $query->select('*')->from('sys_file_storage');
        $result = $query->execute();
        $rows = $result->fetchAllAssociative();

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['uid']);
    }
}
