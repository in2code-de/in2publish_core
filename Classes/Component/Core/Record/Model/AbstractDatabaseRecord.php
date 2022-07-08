<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

abstract class AbstractDatabaseRecord extends AbstractRecord
{
    // Defaults for values if the given CTRL key does not exist in the TCA ctrl section
    protected const CTRL_DEFAULT = [
        'languageField' => 0,
        'transOrigPointerField' => 0,
        'delete' => false,
    ];
    protected string $table;

    public function getClassification(): string
    {
        return $this->table;
    }

    public function getForeignIdentificationProps(): array
    {
        return [
            'uid' => $this->getId(),
        ];
    }

    public function getLanguage(): int
    {
        return $this->getCtrlProp('languageField');
    }

    public function getTransOrigPointer(): int
    {
        return $this->getCtrlProp('transOrigPointerField');
    }

    protected function getCtrlProp(string $ctrlName)
    {
        $value = self::CTRL_DEFAULT[$ctrlName] ?? null;

        $valueField = $GLOBALS['TCA'][$this->table]['ctrl'][$ctrlName] ?? null;

        if (null !== $valueField) {
            $value = $this->getProp($valueField) ?? $value;
        }

        return $value;
    }
}
