<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\Publishing;

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Model\RunningRequest;
use In2code\In2publishCore\Domain\Repository\RunningRequestRepository;
use In2code\In2publishCore\Event\DetermineIfRecordIsPublishing;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use In2code\In2publishCore\Event\VoteIfRecordIsPublishable;
use TYPO3\CMS\Core\SingletonInterface;

use function register_shutdown_function;

class RunningRequestService implements SingletonInterface
{
    /** @var RunningRequestRepository */
    protected $runningRequestRepository;

    protected $shutdownFunctionRegistered = false;

    public function __construct(RunningRequestRepository $runningRequestRepository)
    {
        $this->runningRequestRepository = $runningRequestRepository;
    }

    public function onRecordPublishingBegan(RecursiveRecordPublishingBegan $event): void
    {
        if (!$this->shutdownFunctionRegistered) {
            $repository = $this->runningRequestRepository;
            $token = $_REQUEST['token'];
            register_shutdown_function(static function () use ($repository, $token) {
                $repository->deleteAllByToken($token);
            });
            $this->shutdownFunctionRegistered = true;
        }

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
