<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

use LogicException;

use function array_diff_assoc;
use function array_keys;

abstract class AbstractRecord implements Record
{
    // Initialize this in your constructor
    protected array $localProps;

    // Initialize this in your constructor
    protected array $foreignProps;

    protected string $state;

    protected bool $hasBeenAskedForRecursiveState = false;

    /**
     * @var array<Record>
     */
    protected array $children = [];

    /**
     * @var array<Record>
     */
    protected array $parents = [];

    /**
     * @var array<Record>
     */
    protected array $translations = [];

    protected ?Record $translationParent = null;

    public function getLocalProps(): array
    {
        return $this->localProps;
    }

    public function getForeignProps(): array
    {
        return $this->foreignProps;
    }

    /**
     * @return scalar
     */
    public function getProp(string $prop)
    {
        return $this->localProps[$prop] ?? $this->foreignProps[$prop] ?? null;
    }

    public function getPropsBySide(string $side): array
    {
        switch ($side) {
            case Record::LOCAL:
                return $this->localProps;
            case Record::FOREIGN:
                return $this->foreignProps;
        }
        throw new LogicException("Side $side is unknown");
    }

    public function addChild(Record $childRecord): void
    {
        $this->children[$childRecord->getClassification()][$childRecord->getId()] = $childRecord;
        $childRecord->addParent($this);
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function addParent(Record $parentRecord): void
    {
        $this->parents[] = $parentRecord;
    }

    public function removeParent(Record $record): void
    {
        foreach (array_keys($this->parents, $record) as $idx) {
            unset ($this->parents[$idx]);
        }
    }

    public function getParents(): array
    {
        return $this->parents;
    }

    public function setTranslationParent(Record $translationParent): void
    {
        if (null !== $this->translationParent) {
            throw new LogicException('Can not add more than one translation parent');
        }
        $this->translationParent = $translationParent;
    }

    public function getTranslationParent(): ?Record
    {
        return $this->translationParent;
    }

    public function addTranslation(Record $childRecord): void
    {
        $language = $childRecord->getLanguage();
        $this->translations[$language][$childRecord->getId()] = $childRecord;
        $childRecord->setTranslationParent($this);
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function removeChild(Record $record): void
    {
        $classification = $record->getClassification();
        unset($this->children[$classification][$record->getId()]);
        if (empty($this->children[$classification])) {
            unset($this->children[$classification]);
        }
        $record->removeParent($this);
    }

    public function isChanged(): bool
    {
        return $this->localProps !== $this->foreignProps;
    }

    protected function calculateState(): string
    {
        $localRecordExists = [] !== $this->localProps;
        $foreignRecordExists = [] !== $this->foreignProps;

        if ($localRecordExists && !$foreignRecordExists) {
            return Record::S_ADDED;
        }
        if (!$localRecordExists && $foreignRecordExists) {
            return Record::S_DELETED;
        }

        $isSoftDeleted = false;
        $deleteField = $GLOBALS['TCA'][$this->getClassification()]['ctrl']['delete'] ?? null;
        if ($deleteField) {
            $isSoftDeleted = $this->localProps[$deleteField];
            if ($isSoftDeleted && $this->foreignProps[$deleteField]) {
                $isSoftDeleted = false;
            }
        }
        if ($isSoftDeleted) {
            return Record::S_SOFT_DELETED;
        }
        $changedProps = $this->getChangedProps();
        if (empty($changedProps)) {
            $movedIndicatorFields = [];
            if (isset($GLOBALS['TCA'][$this->getClassification()])) {
                $movedIndicatorFields[] = 'pid';
            }

            $sortByField = $GLOBALS['TCA'][$this->getClassification()]['ctrl']['sortby'] ?? null;
            if (null !== $sortByField) {
                $movedIndicatorFields[] = $sortByField;
            }

            foreach ($movedIndicatorFields as $movedIndicatorField) {
                if ($this->localProps[$movedIndicatorField] !== $this->foreignProps[$movedIndicatorField]) {
                    return Record::S_MOVED;
                }
            }
            return Record::S_UNCHANGED;
        }

        return Record::S_CHANGED;
    }

    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @inheritDoc
     */
    public function getStateRecursive(): string
    {
        $state = $this->getState();
        if ($state !== Record::S_UNCHANGED || $this->hasBeenAskedForRecursiveState) {
            return $state;
        }
        $this->hasBeenAskedForRecursiveState = true;
        foreach ($this->children as $table => $records) {
            if ('pages' === $table) {
                continue;
            }
            foreach ($records as $record) {
                $state = $record->getStateRecursive();
                if ($state !== Record::S_UNCHANGED) {
                    $this->hasBeenAskedForRecursiveState = false;
                    return Record::S_CHANGED;
                }
            }
        }
        $this->hasBeenAskedForRecursiveState = false;
        return Record::S_UNCHANGED;
    }

    public function getLanguage(): int
    {
        return 0;
    }

    public function getTransOrigPointer(): int
    {
        return 0;
    }

    public function getChangedProps(): array
    {
        return array_keys(array_diff_assoc($this->localProps, $this->foreignProps));
    }
}
