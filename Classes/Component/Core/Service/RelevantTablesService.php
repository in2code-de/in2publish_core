<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Service;

use In2code\In2publishCore\Component\Core\Service\Config\ExcludedTablesServiceInjection;
use In2code\In2publishCore\Component\Core\Service\Database\TableContentService;

use function array_merge;
use function array_unique;

class RelevantTablesService
{
    use ExcludedTablesServiceInjection;

    protected TableContentService $tableContentService;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function __construct(TableContentService $tableContentService)
    {
        $this->tableContentService = $tableContentService;
    }

    public function getEmptyAndExcludedTables(): array
    {
        $excludedTables = $this->excludedTablesService->getAllExcludedTables();
        $emptyTables = $this->tableContentService->getAllEmptyTables();
        return array_unique(array_merge($excludedTables, $emptyTables));
    }

    public function getAllNonEmptyNonExcludedTcaTables(): array
    {
        $tables = $this->excludedTablesService->getAllNonExcludedTcaTables();

        foreach ($tables as $idx => $table) {
            if ($this->tableContentService->isEmptyTable($table)) {
                unset($tables[$idx]);
            }
        }

        return $tables;
    }

    /**
     * @param array<string> $tables
     * @return array<string>
     */
    public function removeExcludedAndEmptyTables(array $tables): array
    {
        $tables = $this->excludedTablesService->removeExcludedTables($tables);
        return $this->tableContentService->removeEmptyTables($tables);
    }

    public function isEmptyOrExcludedTable(string $table): bool
    {
        return $this->excludedTablesService->isExcludedTable($table)
            || $this->tableContentService->isEmptyTable($table);
    }
}
