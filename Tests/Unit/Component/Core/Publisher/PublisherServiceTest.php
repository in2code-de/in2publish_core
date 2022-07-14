<?php

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\Publisher\DatabaseRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\PublisherService;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\TaskExecutionService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass PublisherService
 */
class PublisherServiceTest extends UnitTestCase
{
    /**
     * @covers ::publishRecordTree
     * @covers ::publishRecord
     */
    public function testPublishRecordTreePublishesAllUnchangedRecordsInTree(): void
    {
        $publisherService = new PublisherService();
        $publisherService->injectEventDispatcher($this->createMock(EventDispatcher::class));
        $publisherService->injectTaskExecutionService($this->createMock(TaskExecutionService::class));
        $databaseRecordPublisher = $this->createMock(DatabaseRecordPublisher::class);
        $databaseRecordPublisher->method('canPublish')->willReturn(true);

        // assertion: publish all unchanged db records in tree
        $databaseRecordPublisher->expects($this->exactly(2))->method('publish');
        $publisherService->addPublisher($databaseRecordPublisher);

        $recordTree = $this->getRecordTree1();

        $publisherService->publishRecordTree($recordTree);
    }

    /**
     * @covers ::publishRecordTree
     * @covers ::publishRecord
     */
    public function testPublishRecordTreePublishesChildRecordsWithoutPages(): void
    {
        $publisherService = new PublisherService();
        $publisherService->injectEventDispatcher($this->createMock(EventDispatcher::class));
        $publisherService->injectTaskExecutionService($this->createMock(TaskExecutionService::class));
        $databaseRecordPublisher = $this->createMock(DatabaseRecordPublisher::class);
        $databaseRecordPublisher->method('canPublish')->willReturn(true);

        // assertion: publish all unchanged db records in tree
        $databaseRecordPublisher->expects($this->exactly(2))->method('publish');
        $publisherService->addPublisher($databaseRecordPublisher);

        $recordTree = $this->getRecordTree2();

        $publisherService->publishRecordTree($recordTree);
    }

    /**
     * @return void
     */
    protected function createRecord(int $uid, string $table = 'tablename', string $state = 'added'): DatabaseRecord
    {
        $record = $this->createMock(DatabaseRecord::class);
        $record->method('getLocalProps')->willReturn(['foo' => 'bar']);
        $record->method('getId')->willReturn($uid);
        $record->method('getClassification')->willReturn($table);
        $record->method('getState')->willReturn($state);
        return $record;
    }

    // RT with 3 DatabaseRecords, one of them unchanged
    protected function getRecordTree1(): RecordTree|MockObject
    {
        $recordTree = $this->createMock(RecordTree::class);
        $recordTree->method('getChildren')->willReturn([
            [
                $this->createRecord(1, 'tablename', 'added'),
                $this->createRecord(2, 'tablename', 'added'),
                $this->createRecord(3, 'tablename', 'unchanged'),
            ],
        ]);
        return $recordTree;
    }

    // RT 1 DatabaseRecord with two child DatabaseRecords, one of them with classification 'pages'
    protected function getRecordTree2(): RecordTree|MockObject
    {
        $recordTree = $this->createMock(RecordTree::class);

        $record1 = $this->createRecord(1, 'tablename', 'added');
        $record2 = $this->createRecord(2, 'tablename', 'added');
        $record3 = $this->createRecord(3, 'pages', 'added');

        $record1->method('getChildren')->willReturn(
            [
                'tablename' => [
                    2 => $record2
                ],
                'pages' => [
                    3 => $record3
                ]
            ]
        );

        $recordTree->method('getChildren')->willReturn([
            [
                $record1
            ],
        ]);
        return $recordTree;
    }

}
