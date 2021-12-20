<?php


namespace In2code\In2publishCore\Domain\Service\Publishing;


use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Repository\RunningRequestRepository;

class RunningRequestService
{
    /**
     * @var RunningRequestRepository
     */
    protected $runningRequestRepository;

    public function __construct(RunningRequestRepository $runningRequestRepository )
    {
        $this->runningRequestRepository = $runningRequestRepository;
    }

    public function isPublishingRequestRunningForThisRecord(Record $record): bool
    {
        return $this->runningRequestRepository->containsRunningRequestForRecord($record);
    }
}
