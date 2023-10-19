<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use Generator;
use In2code\In2publishCore\Component\Core\Reason\Reasons;

interface Record extends Node
{
    public const LOCAL = 'local';
    public const FOREIGN = 'foreign';
    public const S_ADDED = 'added';
    public const S_CHANGED = 'changed';
    public const S_MOVED = 'moved';
    public const S_SOFT_DELETED = 'soft_deleted';
    public const S_DELETED = 'deleted';
    public const S_UNCHANGED = 'unchanged';

    public function getLocalProps(): array;

    public function setLocalProps(array $localProps): void;

    public function addLocalProp(string $prop, $value): void;

    public function getForeignProps(): array;

    public function setForeignProps(array $foreignProps): void;

    /**
     * Return an associative array which contains field names and values to identify
     * the record in the foreign data source (e.g. database or file system)
     *
     * @return array<string, scalar>
     */
    public function getForeignIdentificationProps(): array;

    /**
     * @return scalar
     */
    public function getProp(string $prop);

    public function getPropsBySide(string $side): array;

    /**
     * @return array The list of names of props that are different.
     */
    public function getChangedProps(): array;

    public function removeChild(Record $record): void;

    public function addParent(Record $parentRecord): void;

    public function removeParent(Record $record): void;

    /**
     * @return array<Record>
     */
    public function getParents(): array;

    public function getParentPageRecord(): ?Record;

    public function setTranslationParent(Record $translationParent): void;

    public function getTranslationParent(): ?Record;

    public function addTranslation(Record $childRecord): void;

    /**
     * @return array<int, array<array-key, Record>>
     */
    public function getTranslations(): array;

    public function isChanged(): bool;

    /**
     * @return string One of the S_* constants
     */
    public function getState(): string;

    /**
     * @return string One of the S_* constants
     */
    public function getStateRecursive(): string;

    /**
     * Prefers the local value. Mostly used to build the record tree.
     *
     * @return int
     */
    public function getLanguage(): int;

    /**
     * Prefers the local value. Mostly used to build the record tree.
     *
     * @return int
     */
    public function getTransOrigPointer(): int;

    /**
     * @return array<Dependency>
     */
    public function getDependencies(): array;

    /**
     * @param array<string, array<array-key, true>> $visited
     * @return Generator<Dependency>
     */
    public function getAllDependencies(array &$visited = []): Generator;

    public function isPublishable(): bool;

    /**
     * @return array<string>
     */
    public function getReasonsWhyTheRecordIsNotPublishableHumanReadable(): array;

    public function hasReasonsWhyTheRecordIsNotPublishable(): bool;

    public function getReasonsWhyTheRecordIsNotPublishable(): Reasons;

    public function hasUnfulfilledDependenciesRecursively(): bool;

    public function isPublishableIgnoringUnreachableDependencies(): bool;

    public function __toString(): string;
}
