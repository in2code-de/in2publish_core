<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\CommonInjection\EventDispatcherInjection;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\TaskExecutionServiceInjection;
use In2code\In2publishCore\Event\BeforePublishingTranslationsEvent;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Event\RecordWasPublished;
use In2code\In2publishCore\Event\RecordWasSelectedForPublishing;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use Throwable;

use function debug_backtrace;
use function sprintf;
use function trigger_error;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const E_USER_DEPRECATED;

class PublisherService
{
    use EventDispatcherInjection;
    use TaskExecutionServiceInjection;

    protected const DEPRECATION_DIRECTLY_INVOKED = '%s%s%s directly invoked \In2code\In2publishCore\Component\Core\Publisher\PublisherService::publishRecordTree. This will not work in in2publish_core v13 anymore. Please use \In2code\In2publishCore\Component\Core\Publisher\PublisherService::publish instead.';
    protected PublisherCollection $publisherCollection;
    /** @var array<string, array<int, true>> */
    protected array $visitedRecords = [];
    protected bool $isPublishAllMode = false;

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

    /**
     * @throws Throwable
     */
    public function publish(PublishingContext $publishingContext): void
    {
        $recordTree = $publishingContext->getRecordTree();
        $this->publishRecordTree($recordTree, $publishingContext->publishChildPages);
    }

    /**
     * Publishes all PUBLISHABLE records (dependencies must NOT be ignored during publishAll)
     * @throws Throwable
     */
    public function publishAllPublishable(PublishingContext $publishingContext): void
    {
        $this->isPublishAllMode = true;
        try {
            $this->publish($publishingContext);
        } finally {
            $this->isPublishAllMode = false;
        }
    }

    /**
     * @throws Throwable
     * @internal This method will be made non-public in in2publish_core v13. Use publish() with PublishingContext
     *     instead.
     */
    public function publishRecordTree(RecordTree $recordTree, bool $includeChildPages = false): void
    {
        // Check if method was called by something else than self::publish and trigger a deprecation.
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $frame = $backtrace[1];
        if ($frame['function'] !== 'publish' || $frame['class'] !== self::class) {
            $message = sprintf(self::DEPRECATION_DIRECTLY_INVOKED, $frame['class'], $frame['type'], $frame['function']);
            trigger_error($message, E_USER_DEPRECATED);
        }
        unset($backtrace, $frame, $message);

        $this->eventDispatcher->dispatch(new RecursiveRecordPublishingBegan($recordTree));

        $this->publisherCollection->start();

        // this is set for the first call to publishRecord, because
        $isTopLevelCall = true;

        try {
            foreach ($recordTree->getChildren() as $records) {
                foreach ($records as $record) {
                    $this->publishRecord($record, $includeChildPages, $isTopLevelCall);
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

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function publishRecord(
        Record $record,
        bool $includeChildPages = false,
        bool $isTopLevelCall = false
    ): void {
        $classification = $record->getClassification();
        $id = $record->getId();

        if (isset($this->visitedRecords[$classification][$id])) {
            return;
        }
        $this->visitedRecords[$classification][$id] = true;

        $this->eventDispatcher->dispatch(new RecordWasSelectedForPublishing($record));

        $wasPublished = $this->publishRecordIfPublishable($record, $isTopLevelCall);

        // Always process children if record was published
        // Also process children if we're not in publishAll mode (individual publishing)
        // In publishAll mode: only process child pages if includeChildPages is true AND parent was published
        // Always process non-page children if parent was published
        $shouldProcessChildren = $wasPublished || !$this->isPublishAllMode;

        if ($shouldProcessChildren) {
            $this->processTranslations($record, $includeChildPages, $wasPublished);
            $this->processChildRecords($record, $includeChildPages, $wasPublished);
        } elseif ($this->isPublishAllMode && $includeChildPages) {
            // in publishAll mode child pages need to be evaluated independently even if parent wasn't published
            $this->processChildPagesIndependently($record, $includeChildPages);
        }
    }

    private function publishRecordIfPublishable(Record $record, bool $isTopLevelCall): bool
    {
        if ($record->hasReasonsWhyTheRecordIsNotPublishable()) {
            return false;
        }

        if ($record->getStateRecursive() === Record::S_UNCHANGED) {
            return false;
        }

        // Determine if record is publishable based on context
        $shouldPublish = $this->shouldRecordBePublished($record, $isTopLevelCall);

        if ($shouldPublish) {
            $this->publisherCollection->publish($record);
            $this->eventDispatcher->dispatch(new RecordWasPublished($record));

            return true;
        }

        return false;
    }

    private function shouldRecordBePublished(Record $record, bool $isTopLevelCall): bool
    {
        // If not in publishAll mode, use the less strict check
        if (!$this->isPublishAllMode) {
            return true;
        }

        if ($isTopLevelCall && $record->getClassification() === 'pages') {
            // Top-level pages and independent child pages must pass strict dependency check
            return $record->isPublishable();
        }

        // For all other cases in publishAll mode use the less strict check since parent context provides validity
        // - Child pages whose parent was published in current run
        // - Non-page records (content elements, etc.)
        // - Translations
        return true;
    }

    private function processTranslations(Record $record, bool $includeChildPages, bool $parentWasPublished = false): void
    {
        $translationEvent = new BeforePublishingTranslationsEvent($record, $includeChildPages);
        $this->eventDispatcher->dispatch($translationEvent);

        if (!$translationEvent->shouldProcessTranslations()) {
            return;
        }

        foreach ($record->getTranslations() as $translatedRecords) {
            foreach ($translatedRecords as $translatedRecord) {
                // Translations are never top-level calls
                $this->publishRecord($translatedRecord, $includeChildPages, false);
            }
        }
    }

    private function processChildPagesIndependently(Record $record, bool $includeChildPages): void
    {
        foreach ($record->getChildren() as $table => $children) {
            if ($table !== 'pages') {
                continue; // Only process child pages independently
            }

            foreach ($children as $child) {
                // Child pages evaluated independently with strict checking
                $this->publishRecord($child, $includeChildPages, true);
            }
        }
    }

    private function processChildRecords(Record $record, bool $includeChildPages, bool $parentWasPublished = false): void
    {
        foreach ($record->getChildren() as $table => $children) {
            // Handle child pages based on mode and settings
            if ($table === 'pages') {
                if (!$includeChildPages && !$this->isPublishAllMode) {
                    // Skip child pages only if not including them AND not in publishAll mode
                    continue;
                }
            }

            foreach ($children as $child) {
                // Determine if child should be treated as top-level for dependency checking
                $isChildTopLevelCall = false;

                if ($table === 'pages') {
                    if ($this->isPublishAllMode) {
                        // In publishAll mode: child pages get strict checking ONLY if their parent
                        // was not published in the current run (independent evaluation)
                        $isChildTopLevelCall = !$parentWasPublished;
                    } elseif ($includeChildPages && !$this->isPublishAllMode) {
                        // Child pages in regular mode with includeChildPages always get strict checking
                        $isChildTopLevelCall = true;
                    }
                }

                $this->publishRecord($child, $includeChildPages, $isChildTopLevelCall);
            }
        }
    }
}