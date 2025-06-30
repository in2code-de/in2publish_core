<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use Exception;
use In2code\In2publishCore\Component\Core\Publisher\DatabaseRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\Publisher;
use In2code\In2publishCore\Component\Core\Publisher\PublisherService;
use In2code\In2publishCore\Component\Core\Publisher\PublishingContext;
use In2code\In2publishCore\Component\Core\Publisher\ReversiblePublisher;
use In2code\In2publishCore\Component\Core\Publisher\TransactionalPublisher;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\TaskExecutionService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Publisher\PublisherService
 */
#[CoversMethod(PublisherService::class, 'publish')]
#[CoversMethod(PublisherService::class, 'publishRecordTree')]
#[CoversMethod(PublisherService::class, 'publishRecord')]
#[CoversMethod(PublisherService::class, 'addPublisher')]
class PublisherServiceTest extends UnitTestCase
{
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
        $publishingContext = new PublishingContext($recordTree);

        $publisherService->publish($publishingContext);
    }

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
        $publishingContext = new PublishingContext($recordTree);

        $publisherService->publish($publishingContext);
    }

    public function testCancelIsCalledWhenExceptionIsThrownDuringPublishing(): void
    {
        $publisherService = new PublisherService();
        $publisherService->injectEventDispatcher($this->createMock(EventDispatcher::class));
        $publisherService->injectTaskExecutionService($this->createMock(TaskExecutionService::class));
        $databaseRecordPublisher = $this->createMock(DatabaseRecordPublisher::class);
        $databaseRecordPublisher->method('canPublish')->willReturn(true);
        $databaseRecordPublisher->method('publish')->willThrowException(new Exception());
        $databaseRecordPublisher->expects($this->once())->method('cancel');
        $publisherService->addPublisher($databaseRecordPublisher);

        $recordTree = $this->getRecordTree1();
        $publishingContext = new PublishingContext($recordTree);

        $this->expectException(Exception::class);
        $publisherService->publish($publishingContext);
    }

    public function testCancelAndReverseAreCalledWhenExceptionIsThrownDuringFinish(): void
    {
        $publisherService = new PublisherService();
        $publisherService->injectEventDispatcher($this->createMock(EventDispatcher::class));
        $publisherService->injectTaskExecutionService($this->createMock(TaskExecutionService::class));
        $recordTree = $this->createMock(RecordTree::class);
        $file = $this->createFileRecord('file1');
        $recordTree->method('getChildren')->willReturn([$file]);

        $fileRecordPublisher = $this->createMock(FileRecordPublisher::class);
        $fileRecordPublisher->method('canPublish')->with($file)->willReturn(true);
        $fileRecordPublisher->method('finish')->willThrowException(new Exception('TestException'));
        $publisherService->addPublisher($fileRecordPublisher);

        $reversibleTransactionalPublisher = $this->getReversibleTransactionalPublisher();
        $publisherService->addPublisher($reversibleTransactionalPublisher);

        $publishingContext = new PublishingContext($recordTree);

        $GLOBALS['number_of_calls_reverse'] = 0;
        $GLOBALS['number_of_calls_cancel'] = 0;
        try {
            $this->expectException(Exception::class);
            $publisherService->publish($publishingContext);
        } finally {
            $this->assertEquals(1, $GLOBALS['number_of_calls_reverse']);
            $this->assertEquals(1, $GLOBALS['number_of_calls_cancel']);
            unset($GLOBALS['number_of_calls_reverse']);
            unset($GLOBALS['number_of_calls_cancel']);
        }
    }

    /**
     * @return void
     */
    protected function createDatabaseRecord(
        int $uid,
        string $table = 'tablename',
        string $state = 'added'
    ): DatabaseRecord {
        $record = $this->createMock(DatabaseRecord::class);
        $record->method('getLocalProps')->willReturn(['foo' => 'bar']);
        $record->method('getId')->willReturn($uid);
        $record->method('getClassification')->willReturn($table);
        $record->method('getState')->willReturn($state);
        return $record;
    }

    protected function createFileRecord(string $identifier, int $storage = 1): FileRecord
    {
        $record = $this->createMock(FileRecord::class);
        $record->method('getProp')->with('storage')->willReturn($storage);
        $record->method('getProp')->with('identifier')->willReturn($identifier);
        return $record;
    }

    // RT with 3 DatabaseRecords, one of them unchanged
    protected function getRecordTree1(): RecordTree
    {
        $recordTree = $this->createMock(RecordTree::class);
        $recordTree->method('getChildren')->willReturn([
            [
                $this->createDatabaseRecord(1, 'tablename', 'added'),
                $this->createDatabaseRecord(2, 'tablename', 'added'),
                $this->createDatabaseRecord(3, 'tablename', 'unchanged'),
            ],
        ]);
        return $recordTree;
    }

    // RT 1 DatabaseRecord with two child DatabaseRecords, one of them with classification 'pages'
    protected function getRecordTree2(): RecordTree
    {
        $recordTree = $this->createMock(RecordTree::class);

        $record1 = $this->createDatabaseRecord(1, 'tablename', 'added');
        $record2 = $this->createDatabaseRecord(2, 'tablename', 'added');
        $record3 = $this->createDatabaseRecord(3, 'pages', 'added');

        $record1->method('getChildren')->willReturn(
            [
                'tablename' => [
                    2 => $record2,
                ],
                'pages' => [
                    3 => $record3,
                ],
            ],
        );

        $recordTree->method('getChildren')->willReturn([
            [
                $record1,
            ],
        ]);
        return $recordTree;
    }

    protected function getReversibleTransactionalPublisher()
    {
        return new class implements Publisher, ReversiblePublisher, TransactionalPublisher {
            public function start(): void
            {
            }

            // method should not be called in test
            public function finish(): void
            {
            }

            public function reverse(): void
            {
                $GLOBALS['number_of_calls_reverse']++;
            }

            // method should not be called in test
            public function cancel(): void
            {
                $GLOBALS['number_of_calls_cancel']++;
            }

            public function canPublish(Record $record): bool
            {
                return true;
            }

            public function publish(Record $record)
            {
            }
        };
    }
}
