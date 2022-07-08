<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\Domain\Model\Record;

interface Publisher
{
    public function canPublish(Record $record): bool;

    public function publish(Record $record);
}
