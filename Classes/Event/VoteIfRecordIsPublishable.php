<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

final class VoteIfRecordIsPublishable extends AbstractVotingEvent
{
    /** @var string */
    private $table;

    /** @var int */
    private $identifier;

    public function __construct(string $table, int $identifier)
    {
        $this->table = $table;
        $this->identifier = $identifier;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function getVotingResult(): bool
    {
        return $this->yes >= $this->no;
    }
}
