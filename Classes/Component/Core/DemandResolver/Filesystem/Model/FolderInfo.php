<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model;

class FolderInfo implements FilesystemInfo
{
    protected int $storage;
    protected string $identifier;
    private string $name;
    /** @var array<FilesystemInfo> */
    protected array $files = [];
    /** @var array<FilesystemInfo> */
    protected array $folders = [];

    public function __construct(int $storage, string $identifier, string $name)
    {
        $this->storage = $storage;
        $this->identifier = $identifier;
        $this->name = $name;
    }

    public function getStorage(): int
    {
        return $this->storage;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addFolder(FilesystemInfo $folderInformation): void
    {
        $this->folders[] = $folderInformation;
    }

    public function getFolders(): array
    {
        return $this->folders;
    }

    public function addFile(FilesystemInfo $fileInformation): void
    {
        $this->files[] = $fileInformation;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function toArray(): array
    {
        return [
            'storage' => $this->storage,
            'identifier' => $this->identifier,
            'name' => $this->name,
        ];
    }
}
