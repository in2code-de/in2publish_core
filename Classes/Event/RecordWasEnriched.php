<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Model\RecordInterface;

final class RecordWasEnriched
{
    /** @var RecordInterface */
    private $record;

    public function __construct(RecordInterface $record)
    {
        $this->record = $record;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }
}
