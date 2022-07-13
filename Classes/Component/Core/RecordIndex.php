<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core;

use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use TYPO3\CMS\Core\Database\Connection;

use function array_key_first;
use function array_search;
use function implode;

class RecordIndex
{
    /**
     * @var RecordCollection<int, Record>
     */
    private RecordCollection $records;
    private DemandsFactory $demandsFactory;
    private Connection $localDatabase;
    private DemandResolver $demandResolver;

    public function __construct()
    {
        $this->records = new RecordCollection();
    }

    public function injectDemandsFactory(DemandsFactory $demandsFactory): void
    {
        $this->demandsFactory = $demandsFactory;
    }

    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }
    public function injectDemandResolver(DemandResolver $demandResolver): void
    {
        $this->demandResolver = $demandResolver;
    }

    /**
     * @param array<Record> $records
     */
    public function addRecords(array $records): void
    {
        foreach ($records as $record) {
            $this->addRecord($record);
        }
    }

    public function addRecord(Record $record): void
    {
        $this->records->addRecord($record);
        foreach ($record->getChildren() as $childRecord) {
            $this->addRecord($childRecord);
        }
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

    public function processDependencies(): void
    {
        $demands = $this->demandsFactory->createDemand();
        $dependencyTree = new RecordTree();

        $this->records->map(function (Record $record) use ($demands, $dependencyTree): void {
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

        $recordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($demands, $recordCollection);

        $recordCollection->addRecords($this->records);

        $this->records->map(function (Record $record) use ($recordCollection): void {
            $dependencies = $record->getDependencies();
            foreach ($dependencies as $dependency) {
                $dependency->fulfill($recordCollection);
            }
        });
    }
}
