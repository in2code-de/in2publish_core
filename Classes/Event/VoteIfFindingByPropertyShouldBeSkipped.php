<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Repository\CommonRepository;

final class VoteIfFindingByPropertyShouldBeSkipped extends AbstractVotingEvent
{
    /** @var CommonRepository */
    private $commonRepository;

    /** @var string */
    private $propertyName;

    /** @var mixed */
    private $propertyValue;

    /** @var string */
    private $tableName;

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
