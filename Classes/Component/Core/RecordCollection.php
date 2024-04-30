<?php

namespace In2code\In2publishCore\Component\Core;

use Closure;
use Generator;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;
use Iterator;
use IteratorAggregate;
use NoRewindIterator;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function array_search;
use function implode;
use function is_array;

class RecordCollection implements IteratorAggregate
{
    /**
     * @var array<string, array<array-key, Record>>
     */
    private array $records = [];

    /**
     * @param iterable<Record>|iterable<array<Record>> $records
     */
    public function __construct(iterable $records = [])
    {
        $this->addRecords($records);
    }

    public function addRecord(Record $record): void
    {
        $this->records[$record->getClassification()][$record->getId()] = $record;
    }

    /**
     * @param iterable<Record>|iterable<array<Record>> $records
     */
    public function addRecords(iterable $records): void
    {
        foreach ($records as $record) {
            if (is_array($record)) {
                $this->addRecords($record);
            } else {
                $this->addRecord($record);
            }
        }
    }

    /**
     * @return array<Record>
     */
    public function getRecords(string $classification = null): array
    {
        if (null === $classification) {
            return $this->records;
        }
        return $this->records[$classification] ?? [];
    }

    /**
     * @param array-key $id
     */
    public function getRecord(string $classification, $id): ?Record
    {
        return $this->records[$classification][$id] ?? null;
    }

    /**
     * @return Generator<Record>
     */
    public function getRecordsFlat(): Generator
    {
        foreach ($this->records as $identifiers) {
            foreach ($identifiers as $identifier => $record) {
                yield $identifier => $record;
            }
        }
    }

    /**
     * @return array<string>
     */
    public function getClassifications(): array
    {
        return array_keys($this->records);
    }

    public function isEmpty(): bool
    {
        return empty($this->records);
    }

    public function contains(string $classification, array $properties): bool
    {
        return null !== $this->getFirstRecordByProperties($classification, $properties);
    }

    public function getFirstRecordByProperties(string $classification, array $properties): ?Record
    {
        if (isset($properties['uid'])) {
            return $this->getRecord($classification, $properties['uid']);
        }
        foreach ($this->records[$classification] as $record) {
            foreach ($properties as $property => $value) {
                if ($record->getProp($property) !== $value) {
                    continue 2;
                }
            }
            return $record;
        }
        return null;
    }

    /**
     * @return array<Record>
     */
    public function getRecordsByProperties(string $classification, array $properties): array
    {
        if (isset($properties['uid'])) {
            $record = $this->getRecord($classification, $properties['uid']);
            if (null !== $record) {
                return [$record];
            }
            return [];
        }
        $records = [];
        foreach ($this->records[$classification] as $record) {
            foreach ($properties as $property => $value) {
                if ($record->getProp($property) !== $value) {
                    continue 2;
                }
            }
            $records[] = $record;
        }
        return $records;
    }

    public function map(Closure $closure): array
    {
        $return = [];
        foreach ($this->getRecordsFlat() as $record) {
            $return[] = $closure($record);
        }
        return $return;
    }

    public function are(Closure $closure): bool
    {
        foreach ($this->getRecordsFlat() as $record) {
            if (!$closure($record)) {
                return false;
            }
        }
        return true;
    }

    public function getIterator(): Iterator
    {
        return new NoRewindIterator($this->getRecordsFlat());
    }

    public function connectTranslations(): void
    {
        $classifications = $this->getClassifications();
        $classifications = $this->removeClassificationsWithoutTranslations($classifications);
        $this->changeTranslationRelationsFromChildToTranslation($classifications);
        $this->moveTranslatedContentFromPageToTranslatedPage($classifications);
    }

    protected function removeClassificationsWithoutTranslations(array $classifications): array
    {
        foreach ($classifications as $idx => $classification) {
            if (
                !isset($GLOBALS['TCA'][$classification])
                || empty($GLOBALS['TCA'][$classification]['ctrl']['languageField'])
                || empty($GLOBALS['TCA'][$classification]['ctrl']['transOrigPointerField'])
            ) {
                unset($classifications[$idx]);
            }
        }
        return $classifications;
    }

    /**
     * Connect all translated records to their language parent
     */
    protected function changeTranslationRelationsFromChildToTranslation(array $classifications): void
    {
        foreach ($classifications as $classification) {
            $transOrigPointerField = $GLOBALS['TCA'][$classification]['ctrl']['transOrigPointerField'];
            $records = $this->getRecords($classification);
            foreach ($records as $record) {
                if (
                    $record->getLanguage() > 0
                    && null === $record->getTranslationParent()
                ) {
                    $transOrigPointer = $record->getProp($transOrigPointerField);
                    if ($transOrigPointer > 0) {
                        $translationParent = $records[$transOrigPointer] ?? null;
                        if (null !== $translationParent) {
                            $translationParent->addTranslation($record);
                            $record->removeChild($translationParent);
                        }
                    }
                }
            }
        }
    }

    /**
     * Move translated records from the default-language-page children to the translated-page children
     */
    protected function moveTranslatedContentFromPageToTranslatedPage(array $classifications): void
    {
        // We only move content to translated pages, not pages themselves.
        // They were connected in changeTranslationRelationsFromChildToTranslation.
        $classifications = $this->removePagesFromClassifications($classifications);

        $pages = $this->getRecords('pages');
        foreach ($pages as $page) {
            /** @var Record[][] $children */
            $children = $page->getChildren();
            foreach ($classifications as $classification) {
                // These $childRecords have a languageField and transOrigPointerField
                foreach ($children[$classification] ?? [] as $record) {
                    $language = $record->getLanguage();
                    if ($language > 0) {
                        $translations = $page->getTranslations()[$language] ?? [];
                        if (!empty($translations)) {
                            $page->removeChild($record);
                            foreach ($translations as $translation) {
                                $translation->addChild($record);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function removePagesFromClassifications(array $classifications): array
    {
        $pagesKey = array_search('pages', $classifications);
        if (false !== $pagesKey) {
            unset($classifications[$pagesKey]);
        }
        return $classifications;
    }

    public function processDependencies(
        RecordTreeBuildRequest $request,
        DemandsFactory $demandsFactory,
        DemandResolver $demandResolver,
        Connection $localDatabase,
        RecordIndex $recordIndex
    ): void {
        $recursionLimit = $request->getDependencyRecursionLimit();
        $dependencyTargets = new RecordCollection($this->records);

        while ($recursionLimit-- > 0 && !$dependencyTargets->isEmpty()) {
            $dependencyTree = new RecordTree();
            $demands = $demandsFactory->createDemand();

            $dependencyTargets->map(function (Record $record) use ($demands, $dependencyTree, $localDatabase): void {
                $dependencies = $record->getDependencies();
                foreach ($dependencies as $dependency) {
                    $classification = $dependency->getClassification();
                    $properties = $dependency->getProperties();
                    if (!$this->getRecordsByProperties($classification, $properties)) {
                        if (isset($properties['uid'])) {
                            $demand = new SelectDemand($classification, '', 'uid', $properties['uid'], $dependencyTree);
                            $demands->addDemand($demand);
                        } else {
                            $property = array_key_first($properties);
                            $value = $properties[$property];
                            unset($properties[$property]);
                            $where = [];
                            foreach ($properties as $property => $value) {
                                $quotedIdentifier = $localDatabase->quoteIdentifier($property);
                                $quotedValue = $localDatabase->quote($value);
                                $where[] = $quotedIdentifier . '=' . $quotedValue;
                            }
                            $where = implode(' AND ', $where);
                            $demand = new SelectDemand($classification, $where, $property, $value, $dependencyTree);
                            $demands->addDemand($demand);
                        }
                    }
                }
            });

            $dependencyTargets = new RecordCollection();
            $demandResolver->resolveDemand($demands, $dependencyTargets);
        }

        $this->map(static function (Record $record) use ($recordIndex): void {
            $dependencies = $record->getDependencies();
            foreach ($dependencies as $dependency) {
                $dependency->fulfill($recordIndex->getRecordCollection());
            }
        });
    }
}
