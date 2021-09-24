<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

final class FolderWasPublished
{
    /** @var int */
    private $storage;

    /** @var string */
    private $folderIdentifier;

    /** @var bool */
    private $success;

    public function __construct(int $storage, string $folderIdentifier, bool $success)
    {
        $this->storage = $storage;
        $this->folderIdentifier = $folderIdentifier;
        $this->success = $success;
    }

    public function getStorage(): int
    {
        return $this->storage;
    }

    public function getFolderIdentifier(): string
    {
        return $this->folderIdentifier;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
