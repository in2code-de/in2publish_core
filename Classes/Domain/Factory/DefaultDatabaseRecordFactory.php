<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Factory;

use In2code\In2publishCore\Domain\Model\DatabaseRecord;

class DefaultDatabaseRecordFactory implements DatabaseRecordFactory
{
    public function getPriority(): int
    {
        return 0;
    }

    public function isResponsible(string $table): bool
    {
        return true;
    }

    public function createDatabaseRecord(
        string $table,
        int $id,
        array $localProps,
        array $foreignProps,
        array $tableIgnoredFields
    ): DatabaseRecord {
        return new DatabaseRecord($table, $id, $localProps, $foreignProps, $tableIgnoredFields);
    }
}
