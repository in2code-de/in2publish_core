<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model;

class FileInfo implements FilesystemInfo
{
    protected int $storage;
    protected string $identifier;
    protected string $name;
    protected string $sha1;
    protected ?string $publicUrl;
    protected int $size;
    protected string $mimetype;
    protected string $extension;
    protected string $folderHash;
    private string $identifierHash;

    public function __construct(
        int $storage,
        string $identifier,
        string $name,
        string $sha1,
        ?string $publicUrl,
        int $size,
        string $mimetype,
        string $extension,
        string $folderHash,
        string $identifierHash
    ) {
        $this->storage = $storage;
        $this->identifier = $identifier;
        $this->name = $name;
        $this->sha1 = $sha1;
        $this->publicUrl = $publicUrl;
        $this->size = $size;
        $this->mimetype = $mimetype;
        $this->extension = $extension;
        $this->folderHash = $folderHash;
        $this->identifierHash = $identifierHash;
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

    public function getSha1(): string
    {
        return $this->sha1;
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getMimetype(): string
    {
        return $this->mimetype;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getFolderHash(): string
    {
        return $this->folderHash;
    }

    public function getIdentifierHash(): string
    {
        return $this->identifierHash;
    }

    public function toArray(): array
    {
        return [
            'storage' => $this->storage,
            'identifier' => $this->identifier,
            'name' => $this->name,
            'sha1' => $this->sha1,
            'publicUrl' => $this->publicUrl,
            'size' => $this->size,
            'mimetype' => $this->mimetype,
            'extension' => $this->extension,
            'folder_hash' => $this->folderHash,
            'identifier_hash' => $this->identifierHash,
        ];
    }
}
