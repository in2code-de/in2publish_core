<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Factory;

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\TtContentDatabaseRecord;

class TtContentDatabaseRecordFactory implements DatabaseRecordFactory
{
    public function getPriority(): int
    {
        return 100;
    }

    public function isResponsible(string $table): bool
    {
        return 'tt_content' === $table;
    }

    public function createDatabaseRecord(
        string $table,
        int $id,
        array $localProps,
        array $foreignProps,
        array $tableIgnoredFields
    ): DatabaseRecord {
        return new TtContentDatabaseRecord($table, $id, $localProps, $foreignProps, $tableIgnoredFields);
    }
}
