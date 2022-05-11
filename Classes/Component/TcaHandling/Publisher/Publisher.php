<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use In2code\In2publish\Domain\Model\Record;

interface Publisher
{
    public function isTransactional(): bool;

    public function isReversible(): bool;

    public function canPublish(Record $record): bool;

    public function publish(Record $record);

    public function commit();
}
