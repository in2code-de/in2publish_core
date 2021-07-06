<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;

class VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped extends AbstractVotingEvent
{
    /** @var CommonRepository */
    protected $commonRepository;

    /** @var RecordInterface */
    protected $record;

    /** @var string */
    protected $tableName;

    public function __construct(CommonRepository $commonRepository, RecordInterface $record, string $tableName)
    {
        $this->commonRepository = $commonRepository;
        $this->record = $record;
        $this->tableName = $tableName;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
