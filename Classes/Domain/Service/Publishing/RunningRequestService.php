<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\Publishing;

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Model\RunningRequest;
use In2code\In2publishCore\Domain\Repository\RunningRequestRepository;
use In2code\In2publishCore\Event\DetermineIfRecordIsPublishing;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use In2code\In2publishCore\Event\VoteIfRecordIsPublishable;

class RunningRequestService
{
    /** @var RunningRequestRepository */
    protected $runningRequestRepository;

    public function __construct(RunningRequestRepository $runningRequestRepository)
    {
        $this->runningRequestRepository = $runningRequestRepository;
    }

    public function onRecordPublishingBegan(RecursiveRecordPublishingBegan $event): void
    {
        /** @var Record $record */
        $record = $event->getRecord();

        $this->writeToRunningRequestsTable($record);
        foreach ($record->getRelatedRecords() as $relatedRecords) {
            foreach ($relatedRecords as $relatedRecord) {
                $this->writeToRunningRequestsTable($relatedRecord);
            }
        }
    }

    protected function writeToRunningRequestsTable(RecordInterface $record): void
    {
        $recordId = $record->getIdentifier();
        $tableName = $record->getTableName();
        $requestToken = $_REQUEST['token'];

        $runningRequest = new RunningRequest($recordId, $tableName, $requestToken);
        $this->runningRequestRepository->add($runningRequest);
    }

    public function onRecordPublishingEnded(RecursiveRecordPublishingEnded $event): void
    {
        $this->runningRequestRepository->deleteAllByToken($_REQUEST['token']);
    }

    public function isPublishable(VoteIfRecordIsPublishable $event): void
    {
        if ($this->runningRequestRepository->hasRunningRequest($event->getIdentifier(), $event->getTable())) {
            $event->voteNo();
        }
    }

    public function isPublishing(DetermineIfRecordIsPublishing $event): void
    {
        if ($this->runningRequestRepository->hasRunningRequest($event->getIdentifier(), $event->getTableName())) {
            $event->setIsPublishing();
        }
    }
}
