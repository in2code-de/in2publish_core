<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;

class VoteIfPageRecordEnrichingShouldBeSkipped extends AbstractVotingEvent
{
    /** @var CommonRepository */
    protected $commonRepository;

    /** @var RecordInterface */
    protected $record;

    public function __construct(CommonRepository $commonRepository, RecordInterface $record)
    {
        $this->commonRepository = $commonRepository;
        $this->record = $record;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }
}
