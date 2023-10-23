<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core;

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactoryInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolverInjection;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;

use function array_key_first;
use function array_search;
use function implode;

class RecordIndex
{
    use LocalDatabaseInjection;
    use DemandsFactoryInjection;
    use DemandResolverInjection;

    /**
     * @var RecordCollection<int, Record>
     */
    private RecordCollection $records;

    public function __construct()
    {
        $this->records = new RecordCollection();
    }

    public function addRecord(Record $record): void
    {
        $this->records->addRecord($record);
    }

    /**
     * @return array<Record>
     */
    public function getRecords(string $classification = null): array
    {
        return $this->records->getRecords($classification);
    }

    /**
     * @param array-key $id
     */
    public function getRecord(string $classification, $id): ?Record
    {
        return $this->records->getRecord($classification, $id);
    }

    /**
     * @return array<Record>
     */
    public function getRecordsByProperties(string $classification, array $properties): array
    {
        return $this->records->getRecordsByProperties($classification, $properties);
    }

    public function connectTranslations(): void
    {
        $classifications = $this->records->getClassifications();
        foreach ($classifications as $idx => $classification) {
            if (
                !isset($GLOBALS['TCA'][$classification])
                || empty($GLOBALS['TCA'][$classification]['ctrl']['languageField'])
                || empty($GLOBALS['TCA'][$classification]['ctrl']['transOrigPointerField'])
            ) {
                unset($classifications[$idx]);
            }
        }

        // Connect all translated records to their language parent
        foreach ($classifications as $classification) {
            $transOrigPointerField = $GLOBALS['TCA'][$classification]['ctrl']['transOrigPointerField'];
            $records = $this->records->getRecords($classification);
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

        $pagesKey = array_search('pages', $classifications);
        if (false !== $pagesKey) {
            unset($classifications[$pagesKey]);
        }
        // Move translated records from the default-language-page children to the translated-page children
        $pages = $this->records->getRecords('pages');
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

    public function processDependencies(RecordTreeBuildRequest $request): void
    {
        $recursionLimit = $request->getDependencyRecursionLimit();
        $dependencyTargets = new RecordCollection($this->records->getIterator());

        while ($recursionLimit-- > 0 && !$dependencyTargets->isEmpty()) {
            $dependencyTree = new RecordTree();
            $demands = $this->demandsFactory->createDemand();

            $dependencyTargets->map(function (Record $record) use ($demands, $dependencyTree): void {
                $dependencies = $record->getDependencies();
                foreach ($dependencies as $dependency) {
                    $classification = $dependency->getClassification();
                    $properties = $dependency->getProperties();
                    if (!$this->records->getRecordsByProperties($classification, $properties)) {
                        if (isset($properties['uid'])) {
                            $demands->addSelect($classification, '', 'uid', $properties['uid'], $dependencyTree);
                        } else {
                            $property = array_key_first($properties);
                            $value = $properties[$property];
                            unset($properties[$property]);
                            $where = [];
                            foreach ($properties as $property => $value) {
                                $quotedIdentifier = $this->localDatabase->quoteIdentifier($property);
                                $quotedValue = $this->localDatabase->quote($value);
                                $where[] = $quotedIdentifier . '=' . $quotedValue;
                            }
                            $where = implode(' AND ', $where);
                            $demands->addSelect($classification, $where, $property, $value, $dependencyTree);
                        }
                    }
                }
            });

            $dependencyTargets = new RecordCollection();
            $this->demandResolver->resolveDemand($demands, $dependencyTargets);
        }

        $this->records->map(function (Record $record): void {
            $dependencies = $record->getDependencies();
            foreach ($dependencies as $dependency) {
                $dependency->fulfill($this->records);
            }
        });
    }
}
