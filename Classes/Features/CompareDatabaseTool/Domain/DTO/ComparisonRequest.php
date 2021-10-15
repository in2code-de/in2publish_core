<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\CompareDatabaseTool\Domain\DTO;

class ComparisonRequest
{
    /** @var array<string> */
    protected $tables = [];

    public function getTables(): array
    {
        return $this->tables;
    }

    public function setTables(array $tables): void
    {
        $this->tables = $tables;
    }
}
