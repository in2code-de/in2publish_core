<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Factory;

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;

interface DatabaseRecordFactory
{
    public function getPriority(): int;

    public function isResponsible(string $table): bool;

    public function createDatabaseRecord(
        string $table,
        int $id,
        array $localProps,
        array $foreignProps,
        array $tableIgnoredFields
    ): DatabaseRecord;
}
