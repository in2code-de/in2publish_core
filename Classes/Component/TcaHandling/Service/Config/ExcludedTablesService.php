<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Service\Config;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;

use function array_diff;
use function array_keys;
use function array_merge;
use function implode;
use function in_array;
use function preg_match_all;

class ExcludedTablesService
{
    protected array $excludeRelatedTables;
    protected array $rtc = [];

    public function injectExcludeRelatedTables(ConfigContainer $configContainer): void
    {
        $this->excludeRelatedTables = $configContainer->get('excludeRelatedTables');
    }

    /**
     * Removes all excluded tables from the given list of tables
     *
     * @param array<string> $tables
     * @return array<string>
     */
    public function removeExcludedTables(array $tables): array
    {
        $excludedTables = $this->getAllExcludedTables();
        return array_diff($tables, $excludedTables);
    }

    /**
     * @return array<string>
     */
    public function getAllNonExcludedTcaTables(): array
    {
        return $this->removeExcludedTables(array_keys($GLOBALS['TCA']));
    }

    /**
     * @return array<string>
     */
    public function getAllExcludedTables(): array
    {
        if (!isset($this->rtc['excludedTables'])) {
            // This array contains regular expressions which every table name has to be tested against.
            $excludeRelatedTables = $this->excludeRelatedTables;

            // Compose a RegEx which matches all excluded tables.
            $regex = '/,(' . implode('|', array_merge($excludeRelatedTables)) . '),/iU';

            // Combine all existing tables into a single string, where each table is delimited by ",,", so preg_match will
            // match two consecutive table names when searching for ",table1, OR ,table2," in ",table1,,table2,".
            // Otherwise, the leading comma of the first table will be consumed by the expression, and it will not match the
            // second table.
            $tables = array_keys($GLOBALS['TCA']);
            $tablesString = ',' . implode(',,', $tables) . ',';
            $matches = [];

            // $matches[1] contains all table names which match all the expressions from excludeRelatedTables.
            preg_match_all($regex, $tablesString, $matches);
            $this->rtc['excludedTables'] = $matches[1] ?? [];
        }
        return $this->rtc['excludedTables'];
    }

    public function isExcludedTable(string $table): bool
    {
        return in_array($table, $this->getAllExcludedTables());
    }
}
