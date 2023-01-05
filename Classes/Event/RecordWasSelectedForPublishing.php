<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Component\Core\Record\Model\Record;

/**
 * @codeCoverageIgnore
 */
final class RecordWasSelectedForPublishing
{
    protected Record $record;

    public function __construct(Record $record)
    {
        $this->record = $record;
    }

    public function getRecord(): Record
    {
        return $this->record;
    }
}
