<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

abstract class AbstractDatabaseRecord extends AbstractRecord
{
    protected string $table;

    public function getClassification(): string
    {
        return $this->table;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return int The UID of the page this record is stored in. If this record is a page record, it returns its default
     *     language id.
     */
    public function getPageId(): int
    {
        if ('pages' === $this->table) {
            $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? null;
            $transOrigPointerField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? null;

            if (null !== $languageField && null !== $transOrigPointerField && $this->getProp($languageField) > 0) {
                return $this->getProp($transOrigPointerField);
            }

            return $this->id;
        }
        return $this->getProp('pid');
    }
}
