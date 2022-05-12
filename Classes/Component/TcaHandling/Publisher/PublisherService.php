<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordTree;
use Throwable;

class PublisherService
{
    protected PublisherCollection $publisherCollection;

    public function __construct()
    {
        $this->publisherCollection = new PublisherCollection();
    }

    public function addPublisher(Publisher $publisher): void
    {
        $this->publisherCollection->addPublisher($publisher);
    }

    public function publishRecordTree(RecordTree $recordTree): void
    {
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
            $this->publisherCollection->publish($record);
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
