<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

interface Record
{
    public function getClassification(): string;

    /**
     * @return array-key
     */
    public function getId();

    public function getLocalProps(): array;

    public function getForeignProps(): array;

    /**
     * @return scalar
     */
    public function getProp(string $propName);

    public function addChild(Record $childRecord): void;

    public function getChildren(): array;

    public function addParent(Record $parentRecord): void;

    public function getParents(): array;

    public function setTranslationParent(Record $translationParent): void;
}
