<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

class VirtualFlexFormRecord extends AbstractDatabaseRecord implements DatabaseEntityRecord
{
    protected string $flexFormPath;

    protected DatabaseEntityRecord $record;

    public function __construct(
        DatabaseEntityRecord $record,
        string $flexFormPath,
        array $localProps,
        array $foreignProps
    ) {
        $this->flexFormPath = $flexFormPath;
        $this->localProps = $localProps;
        $this->foreignProps = $foreignProps;
        $this->record = $record;
    }

    public function getClassification(): string
    {
        return $this->flexFormPath;
    }

    public function getId(): int
    {
        return $this->record->getId();
    }

    public function getPageId(): int
    {
        return $this->record->getPageId();
    }

    public function addChild(Record $childRecord): void
    {
        $this->record->addChild($childRecord);
    }
}
