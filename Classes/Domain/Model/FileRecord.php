<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

class FileRecord extends AbstractRecord
{
    public const FILE_CLASSIFICATION = '#file';

    public function __construct(array $localProps, array $foreignProps)
    {
        $this->localProps = $localProps;
        $this->foreignProps = $foreignProps;

        $this->state = $this->calculateState();
    }

    public function getClassification(): string
    {
        return self::FILE_CLASSIFICATION;
    }

    public function getId(): string
    {
        return $this->getProp('storage') . ':' . $this->getProp('identifier');
    }

    public function getForeignIdentificationProps(): array
    {
        return [];
    }
}
