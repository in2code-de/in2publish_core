<?php

namespace In2code\In2publishCore\Domain\Model;

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
        $this->children[$record->getClassification()][$record->getId()] = $record;
    }
}
