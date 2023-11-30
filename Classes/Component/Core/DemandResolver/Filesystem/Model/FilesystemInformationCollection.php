<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model;

use Generator;
use IteratorAggregate;

class FilesystemInformationCollection implements IteratorAggregate
{
    protected array $information = [];

    public function addFilesystemInfo(FilesystemInfo $filesystemInformation): void
    {
        $storage = $filesystemInformation->getStorage();
        $identifier = $filesystemInformation->getIdentifier();
        $this->information[$storage][$identifier] = $filesystemInformation;
    }

    public function getInfo(int $storage, string $identifier): FilesystemInfo
    {
        return $this->information[$storage][$identifier];
    }

    /**
     * @return Generator<FilesystemInfo>
     */
    public function getIterator(): Generator
    {
        foreach ($this->information as $identifiers) {
            foreach ($identifiers as $information) {
                yield $information;
            }
        }
    }
}
