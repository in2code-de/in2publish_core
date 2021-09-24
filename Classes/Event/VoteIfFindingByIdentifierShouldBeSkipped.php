<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Repository\CommonRepository;

final class VoteIfFindingByIdentifierShouldBeSkipped extends AbstractVotingEvent
{
    /** @var CommonRepository */
    private $commonRepository;

    /** @var int */
    private $identifier;

    /** @var string */
    private $tableName;

    public function __construct(CommonRepository $commonRepository, int $identifier, string $tableName)
    {
        $this->commonRepository = $commonRepository;
        $this->identifier = $identifier;
        $this->tableName = $tableName;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
