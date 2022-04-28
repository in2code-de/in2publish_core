<?php

namespace In2code\In2publishCore\Component\TcaHandling;

use In2code\In2publishCore\Domain\Model\Node;

class Demands
{
    const RECORD_KEY_DELIMITER = "\0";
    private array $demand = [
        'select' => [],
        'join' => [],
    ];

    /**
     * @param int|string $value
     */
    public function addSelect(
        string $from,
        string $additionalWhere,
        string $property,
        $value,
        Node $record
    ): void {
        $this->demand['select'][$from][$additionalWhere][$property][$value][$this->uniqueRecordKey($record)] = $record;
    }

    /**
     * @param int|string $value
     */
    public function addJoin(
        string $mmTable,
        string $joinTable,
        string $additionalWhere,
        string $property,
        $value,
        Node $record
    ): void {
        $uniqueRecordKey = $this->uniqueRecordKey($record);
        $this->demand['join'][$mmTable][$joinTable][$additionalWhere][$property][$value][$uniqueRecordKey] = $record;
    }

    public function uniqueRecordKey(Node $record): string
    {
        return $record->getClassification() . self::RECORD_KEY_DELIMITER . $record->getId();
    }

    /**
     * @return array<string, array<string, array<string, array<mixed, array<string, Node>>>>>
     */
    public function getSelect(): array
    {
        return $this->demand['select'];
    }

    /**
     * @return array<string, array<string, array<string, array<string, array<mixed, array<string, Node>>>>>>
     */
    public function getJoin(): array
    {
        return $this->demand['join'];
    }

}
