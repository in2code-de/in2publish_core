<?php

namespace In2code\In2publishCore\Component\Core\RecordTree;

use In2code\In2publishCore\Component\Core\Record\Model\Node;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

class RecordTree implements Node
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

    public function getClassification(): string
    {
        return '_root';
    }

    public function getId(): int
    {
        return -1;
    }

    public function getChild(string $table, int $id): ?Record
    {
        return $this->children[$table][$id] ?? null;
    }
}
