<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use function array_filter;
use function array_unique;
use function array_values;
use function in_array;

final class RequiredTablesWereIdentified
{
    private array $tables;

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

    public function getTables(): array
    {
        return $this->tables;
    }

    public function setTables($tables): void
    {
        $this->tables = array_values(array_filter(array_unique($tables)));
    }
}
