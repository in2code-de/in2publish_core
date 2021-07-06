<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\RecordInterface;

final class AllRelatedRecordsWereAddedToOneRecord
{
    /** @var RecordFactory */
    private $recordFactory;

    /** @var RecordInterface */
    private $record;

    public function __construct(RecordFactory $recordFactory, RecordInterface $record)
    {
        $this->recordFactory = $recordFactory;
        $this->record = $record;
    }

    public function getRecordFactory(): RecordFactory
    {
        return $this->recordFactory;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }
}
