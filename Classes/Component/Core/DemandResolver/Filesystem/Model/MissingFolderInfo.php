<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model;

class MissingFolderInfo implements FilesystemInfo
{
    protected int $storage;
    protected string $identifier;

    public function __construct(int $storage, string $identifier)
    {
        $this->storage = $storage;
        $this->identifier = $identifier;
    }

    public function getStorage(): int
    {
        return $this->storage;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function toArray(): array
    {
        return [];
    }
}
