<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

class FileRecord extends AbstractRecord
{
    public const CLASSIFICATION = '_file';

    public function __construct(array $localProps, array $foreignProps)
    {
        $this->localProps = $localProps;
        $this->foreignProps = $foreignProps;

        $this->state = $this->calculateState();
    }

    protected function calculateState(): string
    {
        $state = parent::calculateState();
        if (Record::S_CHANGED === $state) {
            $state = Record::S_MOVED;
        }
        return $state;
    }

    public function getClassification(): string
    {
        return self::CLASSIFICATION;
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
