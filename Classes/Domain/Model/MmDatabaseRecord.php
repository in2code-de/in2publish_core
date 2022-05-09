<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

class MmDatabaseRecord extends AbstractDatabaseRecord
{
    protected string $propertyHash;

    public function __construct(string $table, string $propertyHash, array $localFields, array $foreignFields)
    {
        $this->table = $table;
        $this->propertyHash = $propertyHash;

        $this->localProps = $localFields;
        $this->foreignProps = $foreignFields;

        $this->state = $this->calculateState();
    }

    public function getId(): string
    {
        return $this->propertyHash;
    }

    public function getForeignIdentificationProps(): array
    {
        return $this->foreignProps;
    }
}
