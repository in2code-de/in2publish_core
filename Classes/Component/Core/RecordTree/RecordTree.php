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
    private ?RecordTreeBuildRequest $request;

    public function __construct(iterable $children = [], ?RecordTreeBuildRequest $request = null)
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
        $this->request = $request;
    }

    public function getRequest(): ?RecordTreeBuildRequest
    {
        return $this->request;
    }

    public function addChild(Record $record): void
    {
        $this->children[$record->getClassification()][$record->getId()] = $record;
    }

    public function removeChild(Record $record): void
    {
        if (array_key_exists($record->getClassification(), $this->children)) {
            if (array_key_exists($record->getId(), $this->children[$record->getClassification()])) {
                unset($this->children[$record->getClassification()][$record->getId()]);
            }
        }
    }

    /**
     * @return array<string, array<int|string, Record>>
     */
    public function getChildren(): array
    {
        return $this->children;
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

    public function getCurrentPage(): ?Record
    {
        if (isset($this->children['pages']) && is_array($this->children['pages']) && !empty($this->children['pages'])) {
            return reset($this->children['pages']);
        }

        return null;
    }
}
