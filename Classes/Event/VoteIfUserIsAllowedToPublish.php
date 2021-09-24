<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

final class VoteIfUserIsAllowedToPublish extends AbstractVotingEvent
{
    public function getVotingResult(): bool
    {
        return $this->yes >= $this->no;
    }
}
