<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\CommonInjection\EventDispatcherInjection;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\TaskExecutionService;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\TaskExecutionServiceInjection;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Event\RecordWasPublished;
use In2code\In2publishCore\Event\RecordWasSelectedForPublishing;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use Throwable;

class PublisherService
{
    use EventDispatcherInjection;
    use TaskExecutionServiceInjection;

    protected PublisherCollection $publisherCollection;
    /** @var array<string, array<int, true>> */
    protected array $visitedRecords = [];

    public function __construct()
    {
        $this->publisherCollection = new PublisherCollection();
    }

    /**
     * Called by the DI container when constructing this service
     */
    public function addPublisher(Publisher $publisher): void
    {
        $this->publisherCollection->addPublisher($publisher);
    }

    public function publish(PublishingContext $publishingContext): void
    {
        $recordTree = $publishingContext->getRecordTree();
        $this->publishRecordTree($recordTree);
    }

    public function publishRecordTree(RecordTree $recordTree, bool $includeChildPages = false): void
    {
        $this->eventDispatcher->dispatch(new RecursiveRecordPublishingBegan($recordTree));

        $this->publisherCollection->start();

        try {
            foreach ($recordTree->getChildren() as $records) {
                foreach ($records as $record) {
                    $this->publishRecord($record, $includeChildPages);
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

    protected function publishRecord(Record $record, bool $includeChildPages = false): void
    {
        $classification = $record->getClassification();
        $id = $record->getId();

        if (isset($this->visitedRecords[$classification][$id])) {
            return;
        }
        $this->visitedRecords[$classification][$id] = true;

        $this->eventDispatcher->dispatch(new RecordWasSelectedForPublishing($record));

        // Do not use Record::isPublishable(). Check only the record's reasons but not dependencies.
        // Dependencies might have been fulfilled during publishing or ignored by the user by choice.
        if (
            $record->getState() !== Record::S_UNCHANGED
            && !$record->hasReasonsWhyTheRecordIsNotPublishable()
        ) {
            // deprecated, remove in v13
            $this->eventDispatcher->dispatch(new PublishingOfOneRecordBegan($record));
            $this->publisherCollection->publish($record);
            // deprecated, remove in v13
            $this->eventDispatcher->dispatch(new PublishingOfOneRecordEnded($record));
            $this->eventDispatcher->dispatch(new RecordWasPublished($record));
        }

        foreach ($record->getChildren() as $table => $children) {
            if ('pages' === $table && !$includeChildPages) {
                continue;
            }
            foreach ($children as $child) {
                $this->publishRecord($child, true);
            }
        }
    }
}
