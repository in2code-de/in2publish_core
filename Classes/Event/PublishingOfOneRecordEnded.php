<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;

final class PublishingOfOneRecordEnded
{
    /** @var string */
    private $tableName;

    /** @var RecordInterface */
    private $record;

    /** @var CommonRepository */
    private $commonRepository;

    public function __construct(string $tableName, RecordInterface $record, CommonRepository $commonRepository)
    {
        $this->tableName = $tableName;
        $this->record = $record;
        $this->commonRepository = $commonRepository;
    }

    public function getTableName(): string
    {
        return $this->tableName;
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
