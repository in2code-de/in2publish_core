<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model;

interface FilesystemInfo
{
    public function getStorage(): int;

    public function getIdentifier(): string;

    public function toArray(): array;
}
