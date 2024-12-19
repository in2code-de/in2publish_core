<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Iterator\IterationControls\SkipChildren;
use In2code\In2publishCore\Component\Core\Record\Iterator\IterationControls\StopIteration;
use In2code\In2publishCore\Component\Core\Record\Iterator\RecordIterator;
use LogicException;

class FolderRecord extends AbstractRecord
{
    public const CLASSIFICATION = '_folder';

    public function __construct(array $localProps, array $foreignProps)
    {
        $this->localProps = $localProps;
        $this->foreignProps = $foreignProps;

        $this->state = $this->calculateState();
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
        throw new LogicException('NOT IMPLEMENTED', 3424165576);
        return $this->getId();
    }

    public function getStateRecursive(): string
    {
        $state = $this->getState();
        if ($state !== Record::S_UNCHANGED) {
            return $state;
        }
        $children = $this->getChildren();
        unset($children[FolderRecord::CLASSIFICATION], $children[FileRecord::CLASSIFICATION]);

        $stateRecursive = Record::S_UNCHANGED;
        $recordIterator = new RecordIterator();
        foreach ($children as $childRecords) {
            foreach ($childRecords as $childRecord) {
                $recordIterator->recurse($childRecord, function (Record $record) use (&$stateRecursive) {
                    if ($record instanceof FolderRecord || $record instanceof FileRecord) {
                        throw new SkipChildren();
                    }
                    if ($record->getState() !== Record::S_UNCHANGED) {
                        $stateRecursive = Record::S_CHANGED;
                        throw new StopIteration();
                    }
                });
            }
        }
        return $stateRecursive;
    }
}
