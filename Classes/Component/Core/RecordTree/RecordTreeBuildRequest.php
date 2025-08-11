<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\RecordTree;

class RecordTreeBuildRequest
{
    private string $table;
    private int $id;
    private int $pageRecursionLimit;
    private int $dependencyRecursionLimit;
    private int $contentRecursionLimit;
    /** @var null|array<int> */
    private ?array $languages;

    public function __construct(
        string $table,
        int $id,
        int $pageRecursionLimit = 0,
        int $dependencyRecursionLimit = 3,
        int $contentRecursionLimit = 8,
        ?array $languages = null
    ) {
        $this->table = $table;
        $this->id = $id;
        $this->pageRecursionLimit = $pageRecursionLimit;
        $this->dependencyRecursionLimit = $dependencyRecursionLimit;
        $this->contentRecursionLimit = $contentRecursionLimit;
        $this->languages = $languages;
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

    public function getDependencyRecursionLimit(): int
    {
        return $this->dependencyRecursionLimit;
    }

    public function getContentRecursionLimit(): int
    {
        return $this->contentRecursionLimit;
    }

    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function withId(int $id): RecordTreeBuildRequest
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }
}
