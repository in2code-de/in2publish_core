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
     * @param string $table e.g. sys_file_storage
     * @param string $field e.g. uid
     * @param int|string $search e.g. 13
     * @return void
     */
    public function unsetSelect(string $table, string $field, $search): void
    {
        foreach ($this->demand['select'][$table] ?? [] as $additionalWhere => $properties) {
            foreach ($properties as $property => $values) {
                if ($property === $field) {
                    unset($this->demand['select'][$table][$additionalWhere][$property][$search]);
                }
                if (empty($this->demand['select'][$table][$additionalWhere][$property])) {
                    unset($this->demand['select'][$table][$additionalWhere][$property]);
                }
            }
            if (empty($this->demand['select'][$table][$additionalWhere])) {
                unset($this->demand['select'][$table][$additionalWhere]);
            }
        }
        if (empty($this->demand['select'][$table])) {
            unset($this->demand['select'][$table]);
        }
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

    /**
     * @param string $mmTable e.g. tx_news_domain_model_news_related_mm
     * @param string $joinTable e.g. tx_news_domain_model_news
     * @param string $field e.g. uid_local
     * @param int|string $search e.g. 25
     * @return void
     */
    public function unsetJoin(string $mmTable,string $joinTable, string $field, $search): void
    {
        foreach ($this->demand['join'][$mmTable][$joinTable] ?? [] as $additionalWhere => $properties) {
            foreach ($properties as $property => $values) {
                if ($property === $field) {
                    unset($this->demand['join'][$mmTable][$joinTable][$additionalWhere][$property][$search]);
                }
                if (empty($this->demand['join'][$mmTable][$joinTable][$additionalWhere][$property])) {
                    unset($this->demand['join'][$mmTable][$joinTable][$additionalWhere][$property]);
                }
            }
            if (empty($this->demand['join'][$mmTable][$joinTable][$additionalWhere])) {
                unset($this->demand['join'][$mmTable][$joinTable][$additionalWhere]);
            }
        }
        if (empty($this->demand['join'][$mmTable][$joinTable])) {
            unset($this->demand['join'][$mmTable][$joinTable]);
        }
        if (empty($this->demand['join'][$mmTable])) {
            unset($this->demand['join'][$mmTable]);
        }
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
