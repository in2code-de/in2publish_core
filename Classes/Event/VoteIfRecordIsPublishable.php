<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

class VoteIfRecordIsPublishable extends AbstractVotingEvent
{
    /** @var string */
    protected $table;

    /** @var int */
    protected $identifier;

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
