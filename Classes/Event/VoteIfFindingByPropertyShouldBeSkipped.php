<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Repository\CommonRepository;

class VoteIfFindingByPropertyShouldBeSkipped extends AbstractVotingEvent
{
    /** @var CommonRepository */
    protected $commonRepository;

    /** @var string */
    protected $propertyName;

    /** @var mixed */
    protected $propertyValue;

    /** @var string */
    protected $tableName;

    public function __construct(
        CommonRepository $commonRepository,
        string $propertyName,
        $propertyValue,
        string $tableName
    ) {
        $this->commonRepository = $commonRepository;
        $this->propertyName = $propertyName;
        $this->propertyValue = $propertyValue;
        $this->tableName = $tableName;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getPropertyValue()
    {
        return $this->propertyValue;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
