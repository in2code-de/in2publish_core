<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Repository\CommonRepository;

class VoteIfRecordShouldBeIgnored extends AbstractVotingEvent
{
    /** @var CommonRepository */
    protected $commonRepository;

    /** @var array */
    protected $localProperties;

    /** @var array */
    protected $foreignProperties;

    /** @var string */
    protected $tableName;

    public function __construct(
        CommonRepository $commonRepository,
        array $localProperties,
        array $foreignProperties,
        string $tableName
    ) {
        $this->commonRepository = $commonRepository;
        $this->localProperties = $localProperties;
        $this->foreignProperties = $foreignProperties;
        $this->tableName = $tableName;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }

    public function getLocalProperties(): array
    {
        return $this->localProperties;
    }

    public function getForeignProperties(): array
    {
        return $this->foreignProperties;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
