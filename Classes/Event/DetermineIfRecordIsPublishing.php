<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use Psr\EventDispatcher\StoppableEventInterface;

final class DetermineIfRecordIsPublishing implements StoppableEventInterface
{
    /** @var bool */
    protected $publishing = false;

    private $tableName;

    private $identifier;

    /**
     * @param string $tableName
     * @param string|int $identifier
     */
    public function __construct(string $tableName, $identifier)
    {
        $this->tableName = $tableName;
        $this->identifier = $identifier;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIsPublishing(): void
    {
        $this->publishing = true;
    }

    public function isPublishing(): bool
    {
        return $this->publishing;
    }

    public function isPropagationStopped(): bool
    {
        return $this->publishing;
    }
}
