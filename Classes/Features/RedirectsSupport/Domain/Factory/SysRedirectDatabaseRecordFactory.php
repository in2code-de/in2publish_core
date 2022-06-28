<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Factory;

use In2code\In2publishCore\Domain\Factory\DatabaseRecordFactory;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\SysRedirectDatabaseRecord;

class SysRedirectDatabaseRecordFactory implements DatabaseRecordFactory
{
    public function getPriority(): int
    {
        return 100;
    }

    public function isResponsible(string $table): bool
    {
        return 'sys_redirect' === $table;
    }

    public function createDatabaseRecord(
        string $table,
        int $id,
        array $localProps,
        array $foreignProps,
        array $tableIgnoredFields
    ): DatabaseRecord {
        return new SysRedirectDatabaseRecord($table, $id, $localProps, $foreignProps, $tableIgnoredFields);
    }
}
