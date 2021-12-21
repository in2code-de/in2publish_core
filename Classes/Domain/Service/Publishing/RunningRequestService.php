<?php


namespace In2code\In2publishCore\Domain\Service\Publishing;


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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function isPublishingRequestRunningForThisRecord(int $identifier, string $tableName): bool
    {
        return $this->runningRequestRepository->existsRunningRequestForRecord($identifier, $tableName);
    }
}
