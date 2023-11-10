<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand\Type;

use In2code\In2publishCore\Component\Core\Record\Model\Node;

class FilesInFolderDemand implements Demand
{
    use UniqueRecordKeyGenerator;

    private int $storage;
    private string $parentFolderIdentifier;
    private Node $record;

    public function __construct(int $storage, string $parentFolderIdentifier, Node $record)
    {
        $this->storage = $storage;
        $this->parentFolderIdentifier = $parentFolderIdentifier;
        $this->record = $record;
    }

    public function addToDemandsArray(array &$demands): void
    {
        $uniqueRecordKey = $this->createUniqueRecordKey($this->record);
        $demands[$this->storage][$this->parentFolderIdentifier][$uniqueRecordKey] = $this->record;
    }

    public function addToMetaArray(array &$meta, array $frame): void
    {
        $meta[$this->storage][$this->parentFolderIdentifier][] = $frame;
    }
}
