<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

abstract class AbstractRecord implements Record
{
    // Initialize this in your constructor
    protected array $localProps;

    // Initialize this in your constructor
    protected array $foreignProps;

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

    public function getProp(string $propName)
    {
        return $this->localProps[$propName] ?? $this->foreignProps[$propName] ?? null;
    }

    public function addChild(Record $childRecord): void
    {
        if ($this->isTranslationParent($childRecord)) {
            $this->addTranslation($childRecord);
            return;
        }

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

    public function getParents(): array
    {
        return $this->parents;
    }

    protected function isTranslationParent(Record $childRecord): bool
    {
        if (!$this->isTranslatedRecord($childRecord)) {
            return false;
        }
        $classification = $this->getClassification();
        if ($classification !== $childRecord->getClassification()) {
            return false;
        }
        $transOrigPointerField = $GLOBALS['TCA'][$classification]['ctrl']['transOrigPointerField'];
        /** @noinspection IfReturnReturnSimplificationInspection */
        if ($this->getId() !== (int)$childRecord->getProp($transOrigPointerField)) {
            return false;
        }
        return true;
    }

    public function setTranslationParent(Record $translationParent): void
    {
        $this->translationParent = $translationParent;
    }

    protected function addTranslation(Record $childRecord): void
    {
        $languageField = $GLOBALS['TCA'][$childRecord->getClassification()]['ctrl']['languageField'];
        $language = $childRecord->getProp($languageField);
        $this->translations[$language][$childRecord->getId()] = $childRecord;
        $childRecord->setTranslationParent($this);
    }

    protected function isTranslatedRecord(Record $childRecord): bool
    {
        $classification = $childRecord->getClassification();
        if (!isset($GLOBALS['TCA'][$classification])) {
            return false;
        }
        $languageField = $GLOBALS['TCA'][$classification]['ctrl']['languageField'] ?? null;
        if (null === $languageField) {
            return false;
        }
        $transOrigPointerField = $GLOBALS['TCA'][$classification]['ctrl']['transOrigPointerField'] ?? null;
        if (null === $transOrigPointerField) {
            return false;
        }
        if (0 >= (int)$childRecord->getProp($languageField)) {
            return false;
        }
        return true;
    }
}
