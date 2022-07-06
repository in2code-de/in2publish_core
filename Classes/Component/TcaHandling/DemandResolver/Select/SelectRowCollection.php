<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\DemandResolver\Select;

class SelectRowCollection
{
    /**
     * @var array<string, array<int, array{row: array, valueMaps: array, property: string}>>
     */
    private array $rows = [];

    public function addRows(string $table, array $rows, array $valueMaps, string $property): void
    {
        foreach ($rows as $uid => $row) {
            $this->addRow($table, $uid, $row, $valueMaps, $property);
        }
    }

    public function addRow(string $table, int $uid, array $row, array $valueMaps, string $property): void
    {
        $this->rows[$table][$uid] = [
            'row' => $row,
            'valueMaps' => $valueMaps,
            'property' => $property,
        ];
    }

    public function getMissingIdentifiers(): array
    {
        $missing = [];

        foreach ($this->rows as $table => $rows) {
            foreach ($rows as $uid => $rowInfo) {
                // Do not add UIDs of rows which were already queried by their UID.
                if ($rowInfo['property'] !== 'uid') {
                    if (empty($rowInfo['row']['local'])) {
                        $missing['local'][$table][] = $uid;
                    }
                    if (empty($rowInfo['row']['foreign'])) {
                        $missing['foreign'][$table][] = $uid;
                    }
                }
            }
        }

        return $missing;
    }

    public function amendRows(string $table, string $side, array $rows): void
    {
        foreach ($rows as $uid => $row) {
            $this->amendRow($table, $uid, $side, $row);
        }
    }

    protected function amendRow(string $table, int $uid, string $side, array $row): void
    {
        $this->rows[$table][$uid]['row'][$side] = $row;
    }

    public function getRows(): array
    {
        return $this->rows;
    }
}
