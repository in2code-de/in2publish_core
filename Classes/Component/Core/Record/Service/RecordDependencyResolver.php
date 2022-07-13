<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Service;

use In2code\In2publishCore\Component\Core\Publisher\Service\PublishingStateService;
use In2code\In2publishCore\Component\Core\Record\Model\Dependency;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\Core\RecordTree\Traverser\RecordTreeTraverser;
use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;
use Throwable;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

class RecordDependencyResolver
{
    protected EventDispatcher $eventDispatcher;
    protected PublishingStateService $publishingStateService;

    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }
    public function injectPublishingStateService(PublishingStateService $publishingStateService): void
    {
        $this->publishingStateService = $publishingStateService;
    }

    /**
     * @return array<array<Dependency>>
     */
    public function getUnmetDependencies(RecordTree $recordTree): array
    {
        $recordsToFetch = [];

        $traverser = new RecordTreeTraverser();
        $traverser->addVisitor(function (string $event, Record $record) use(&$recordsToFetch) {
            if (RecordTreeTraverser::EVENT_ENTER !== $event) {
                return;
            }
            $dependencies = $record->getDependencies();
            foreach ($dependencies as $dependency) {
                if (!$dependency->isFulfilled()) {
                    $classification = $dependency->getClassification();
                    $properties = $dependency->getProperties();
                    $recordsToFetch[] = [
                        'classification' => $classification,
                        'properties' => $properties,
                        'resolver' => function (Record $record) use ($dependency) {
                        }
                    ];

                }
            }
        });
        $traverser->run($recordTree);

        return $recordsToFetch;
    }

    /**
     * @return array<array<Dependency>>
     */
    public function getUnmetDependencies2(RecordTree $recordTree): array
    {
        $reasons = [];
        $pageRecords = $this->extractPageRecords($recordTree);
        foreach ($pageRecords as $pageRecord) {
            try {
                $recordsThatWouldBePublished = $this->getRecordsThatWouldBePublished2($recordTree);
            } catch (Throwable $exception) {
            }

            $traverser = new RecordTreeTraverser();
            $traverser->addVisitor(function (string $event, Record $record) use($recordsThatWouldBePublished, &$reasons) {
                if (RecordTreeTraverser::EVENT_ENTER !== $event) {
                    return;
                }
                $reasons1 = $this->publishingStateService->getReasonsWhyTheRecordIsNotPublishable(
                    $recordsThatWouldBePublished,
                    $record
                );
                if (!$reasons1->isEmpty()) {
                    $reasons[$record->getClassification()][$record->getId()] = $reasons1;
                }
            });
            $subTree = new RecordTree([$pageRecord]);
            $traverser->run($subTree);
        }

        return $reasons;
    }

    public function getRecordsThatWouldBePublished2(RecordTree $recordTree): RecordCollection
    {
        $traverser = new RecordTreeTraverser();

        $pageRecords = new RecordCollection();
        $traverser->addVisitor(function (string $event, Record $record) use (&$pageRecords) {
            if (RecordTreeTraverser::EVENT_ENTER !== $event) {
                return;
            }

            $votingEvent = new CollectReasonsWhyTheRecordIsNotPublishable($record);
            $this->eventDispatcher->dispatch($votingEvent);
            if ($votingEvent->isPublishable()) {
                $pageRecords->addRecord($record);
            }
        });

        $traverser->run($recordTree);
        return $pageRecords;
    }
    public function getRecordsThatWouldBePublished(RecordTree $recordTree): RecordCollection
    {
        $publishedRecords = new RecordCollection();
        foreach ($recordTree->getChildren() as $records) {
            foreach ($records as $record) {
                $this->publishRecord($record, $publishedRecords);
            }
        }
        return $publishedRecords;
    }

    protected function publishRecord(
        Record $record,
        RecordCollection $publishedRecords,
        array &$visitedRecords = []
    ): void {
        $classification = $record->getClassification();
        $id = $record->getId();

        if (isset($visitedRecords[$classification][$id])) {
            return;
        }
        $visitedRecords[$classification][$id] = true;

        $event = new CollectReasonsWhyTheRecordIsNotPublishable($record);
        $this->eventDispatcher->dispatch($event);
        if (!$event->isPublishable()) {
            $publishedRecords->addRecord($record);
        }

        foreach ($record->getChildren() as $table => $children) {
            if ('pages' !== $table) {
                foreach ($children as $child) {
                    $this->publishRecord($child, $publishedRecords, $visitedRecords);
                }
            }
        }
    }

    /**
     * @return array<Record>
     */
    public function extractPageRecords(RecordTree $recordTree): array
    {
        $traverser = new RecordTreeTraverser();

        $pageRecords = [];
        $traverser->addVisitor(static function (string $event, Record $record) use (&$pageRecords) {
            if (RecordTreeTraverser::EVENT_ENTER !== $event) {
                return;
            }
            if ('pages' === $record->getClassification()) {
                $pageRecords[] = $record;
            }
        });

        $traverser->run($recordTree);
        return $pageRecords;
    }

    protected function getDependencies(RecordTree $recordTree): array
    {
        $traverser = new RecordTreeTraverser();

        $isRoot = true;
        $dependencies = [];
        $traverser->addVisitor(static function (string $event, Record $record) use (&$dependencies, &$isRoot): ?string {
            if (!$isRoot && 'pages' === $record->getClassification()) {
                return RecordTreeTraverser::OP_IGNORE;
            }
            $isRoot = false;
            if (RecordTreeTraverser::EVENT_ENTER !== $event) {
                return null;
            }
            foreach ($record->getInheritedDependencies() as $dependency) {
                $dependencies[] = $dependency;
            }
            return null;
        });

        $traverser->run($recordTree);
        return $dependencies;
    }
}
