<?php


namespace In2code\In2publishCore\EventListener;


use In2code\In2publishCore\Domain\Service\Publishing\RunningRequestService;
use In2code\In2publishCore\Event\VoteIfRecordIsPublishable;

class PublishingRequestIsRunningVoter
{
    /** @var RunningRequestService */
    protected $runningRequestService;

    public function __construct(RunningRequestService $runningRequestService)
    {
        $this->runningRequestService = $runningRequestService;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function isPublishable(VoteIfRecordIsPublishable $event): void
    {
        $tableName = $event->getTable();
        $identifier = $event->getIdentifier();
        if ($this->runningRequestService->isPublishingRequestRunningForThisRecord($identifier, $tableName)) {
            $event->voteNo();
        } else {
            $event->voteYes();
        }
    }
}
