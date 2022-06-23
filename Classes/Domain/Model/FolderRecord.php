<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

use LogicException;

class FolderRecord extends AbstractRecord
{
    public const CLASSIFICATION = '#folder';
    private string $combinedIdentifier;

    public function __construct(string $combinedIdentifier, array $localProps, array $foreignProps)
    {
        $this->combinedIdentifier = $combinedIdentifier;
        $this->localProps = $localProps;
        $this->foreignProps = $foreignProps;

        $this->state = $this->calculateState();
    }

    public function getClassification(): string
    {
        return self::CLASSIFICATION;
    }

    public function getId()
    {
        return $this->combinedIdentifier;
    }

    public function getForeignIdentificationProps(): array
    {
        throw new LogicException('NOT IMPLEMENTED');
        return $this->getId();
    }
}
