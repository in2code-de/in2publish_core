<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core;

class RecordTreeBuildRequest
{
    private string $table;
    private int $id;
    private int $pageRecursionLimit;

    public function __construct(string $table, int $id, int $pageRecursionLimit)
    {
        $this->table = $table;
        $this->id = $id;
        $this->pageRecursionLimit = $pageRecursionLimit;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPageRecursionLimit(): int
    {
        return $this->pageRecursionLimit;
    }

    public function withId(int $id): RecordTreeBuildRequest
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }
}
