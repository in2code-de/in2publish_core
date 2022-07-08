<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling;

use In2code\In2publishCore\Domain\Model\Record;

use function array_search;

class RecordIndex
{
    /**
     * @var RecordCollection<int, Record>
     */
    private RecordCollection $records;

    public function __construct()
    {
        $this->records = new RecordCollection();
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
}
