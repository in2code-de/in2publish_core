<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

class DatabaseRecord extends AbstractDatabaseRecord
{
    protected int $id;

    public function __construct(string $table, int $propertyHash, array $localFields, array $foreignFields)
    {
        $this->table = $table;
        $this->id = $propertyHash;

        $this->localProps = $localFields;
        $this->foreignProps = $foreignFields;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
