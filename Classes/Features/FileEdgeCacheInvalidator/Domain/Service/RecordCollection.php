<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Service;

class RecordCollection
{
    /** @var int[] */
    protected $pages = [];

    /** @var array<string, int[]> */
    protected $records = [];

    public function addRecord(string $table, int $uid)
    {
        if ('pages' === $table) {
            $this->pages[$uid] = $uid;
        } else {
            $this->records[$table][$uid] = $uid;
        }
    }

    /** @return int[] */
    public function getPages(): array
    {
        return $this->pages;
    }

    /** @return array<string, int[]> */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function hasPages(): bool
    {
        return !empty($this->pages);
    }
}
