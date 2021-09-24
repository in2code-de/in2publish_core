<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use function array_filter;
use function array_unique;
use function array_values;
use function in_array;

final class RequiredTablesWereIdentified
{
    private $tables;

    public function __construct($tables)
    {
        $this->setTables($tables);
    }

    public function addTable(string $table): void
    {
        if (!in_array($table, $this->tables, true)) {
            $this->tables[] = $table;
        }
    }

    public function addTables(array $tables): void
    {
        foreach ($tables as $table) {
            $this->addTable($table);
        }
    }

    public function removeTable(string $tableToRemove): void
    {
        foreach ($this->tables as $index => $table) {
            if ($table === $tableToRemove) {
                unset($this->tables[$index]);
                $this->tables = array_values($this->tables);
                return;
            }
        }
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function setTables($tables): void
    {
        $this->tables = array_values(array_filter(array_unique($tables)));
    }
}
