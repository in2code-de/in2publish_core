<?php

namespace In2code\In2publishCore\Domain\Model;

use function In2code\In2publishCore\merge_record;

class RecordTree
{
    /**
     * @var array<string, array<int|string, Record>>
     */
    private array $children = [];

    /**
     * @return array<string, array<int|string, Record>>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(Record $record): void
    {
        merge_record($this->children, $record);
    }
}
