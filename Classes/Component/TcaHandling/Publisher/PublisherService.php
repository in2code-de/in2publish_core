<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\TaskExecutionService;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordTree;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use In2code\In2publishCore\Event\VoteIfRecordShouldBeSkipped;
use Throwable;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

class PublisherService
{
    protected PublisherCollection $publisherCollection;
    protected EventDispatcher $eventDispatcher;
    protected TaskExecutionService $taskExecutionService;

    public function __construct()
    {
        $this->publisherCollection = new PublisherCollection();
    }

    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function injectTaskExecutionService(TaskExecutionService $taskExecutionService): void
    {
        $this->taskExecutionService = $taskExecutionService;
    }

    public function addPublisher(Publisher $publisher): void
    {
        $this->publisherCollection->addPublisher($publisher);
    }

    public function publishRecordTree(RecordTree $recordTree): void
    {
        $this->eventDispatcher->dispatch(new RecursiveRecordPublishingBegan($recordTree));

        $this->publisherCollection->start();

        try {
            $visitedRecords = [];
            foreach ($recordTree->getChildren() as $records) {
                foreach ($records as $record) {
                    $this->publishRecord($record, $visitedRecords);
                }
            }
        } catch (Throwable $exception) {
            $this->publisherCollection->cancel();
            throw $exception;
        }

        try {
            $this->publisherCollection->finish();
        } catch (Throwable $exception) {
            $this->publisherCollection->cancel();
            $this->publisherCollection->reverse();
            throw $exception;
        }

        $this->eventDispatcher->dispatch(new RecursiveRecordPublishingEnded($recordTree));

        $this->taskExecutionService->runTasks();
    }

    protected function publishRecord(Record $record, &$visitedRecords = []): void
    {
        $classification = $record->getClassification();
        $id = $record->getId();

        if (isset($visitedRecords[$classification][$id])) {
            return;
        }
        $visitedRecords[$classification][$id] = true;

        if ($record->getState() !== Record::S_UNCHANGED) {
            $event = new VoteIfRecordShouldBeSkipped($record);
            $this->eventDispatcher->dispatch($event);
            if (!$event->getVotingResult()) {
                $this->eventDispatcher->dispatch(new PublishingOfOneRecordBegan($record));
                $this->publisherCollection->publish($record);
                $this->eventDispatcher->dispatch(new PublishingOfOneRecordEnded($record));
            }
        }

        foreach ($record->getChildren() as $table => $children) {
            if ('pages' !== $table) {
                foreach ($children as $child) {
                    $this->publishRecord($child, $visitedRecords);
                }
            }
        }
    }
}
