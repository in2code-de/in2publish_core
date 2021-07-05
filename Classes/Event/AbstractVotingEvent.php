<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

abstract class AbstractVotingEvent
{
    protected $yes = 0;

    protected $no = 0;

    public function voteYes(int $count = 1): void
    {
        $this->yes += $count;
    }

    public function voteNo(int $count = 1): void
    {
        $this->no += $count;
    }

    public function getVotingResult(): bool
    {
        return $this->yes > $this->no;
    }
}
