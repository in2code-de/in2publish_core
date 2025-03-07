<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

final class NoPageIdFoundEvent
{
    private $identifier;
    private $table;
    private $pageIdentifier;

    public function __construct($identifier, ?string $table, $pageIdentifier)
    {
        $this->identifier = $identifier;
        $this->table = $table;
        $this->pageIdentifier = $pageIdentifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getPageIdentifier()
    {
        return $this->pageIdentifier;
    }

    public function setPageIdentifier($pageIdentifier): void
    {
        $this->pageIdentifier = $pageIdentifier;
    }
}