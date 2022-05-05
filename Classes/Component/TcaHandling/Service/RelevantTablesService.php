<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Service;

use In2code\In2publishCore\Component\TcaHandling\Service\Config\ExcludedTablesService;
use In2code\In2publishCore\Component\TcaHandling\Service\Database\TableContentService;

use function array_merge;
use function array_unique;

class RelevantTablesService
{
    protected TableContentService $tableContentService;
    protected ExcludedTablesService $excludedTablesService;

    public function injectTableContentService(TableContentService $tableContentService): void
    {
        $this->tableContentService = $tableContentService;
    }

    public function injectExcludedTablesService(ExcludedTablesService $excludedTablesService): void
    {
        $this->excludedTablesService = $excludedTablesService;
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
}
