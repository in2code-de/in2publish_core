<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;

final class RecursiveRecordPublishingBegan
{
    /** @var RecordInterface */
    private $record;

    /** @var CommonRepository */
    private $commonRepository;

    public function __construct(RecordInterface $record, CommonRepository $commonRepository)
    {
        $this->record = $record;
        $this->commonRepository = $commonRepository;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }
}
