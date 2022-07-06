<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Demand\Resolver;

class JoinRowCollection
{
    /**
     * @var array<string, array<string, array<string, array{row: array, valueMaps: array, property: string}>>>
     */
    private array $rows = [];

    public function addRows(string $joinTable, string $table, array $rows, array $valueMaps, string $property): void
    {
        foreach ($rows as $mmId => $row) {
            $this->addRow($joinTable, $table, $mmId, $row, $valueMaps, $property);
        }
    }

    public function addRow(
        string $joinTable,
        string $table,
        string $mmId,
        array $row,
        array $valueMaps,
        string $property
    ): void {
        $this->rows[$joinTable][$table][$mmId] = [
            'row' => $row,
            'valueMaps' => $valueMaps,
            'property' => $property,
        ];
    }

    public function getMissingIdentifiers(): array
    {
        $missing = [];

        foreach ($this->rows as $joinTable => $tables) {
            foreach ($tables as $table => $rows) {
                foreach ($rows as $mmId => $rowInfo) {
                    $row = $rowInfo['row'];

                    if (!empty($row['foreign']['table']['uid']) && empty($row['local']['table']['uid'])) {
                        $uid = $row['foreign']['table']['uid'];
                        $missing['local'][$table][$joinTable][$uid][] = $mmId;
                    }
                    if (!empty($row['local']['table']['uid']) && empty($row['foreign']['table']['uid'])) {
                        $uid = $row['local']['table']['uid'];
                        $missing['foreign'][$table][$joinTable][$uid][] = $mmId;
                    }
                }
            }
        }

        return $missing;
    }

    public function amendRow(string $joinTable, string $table, string $mmId, string $side, array $row): void
    {
        $this->rows[$joinTable][$table][$mmId]['row'][$side]['table'] = $row;
    }

    public function getRows(): array
    {
        return $this->rows;
    }
}
