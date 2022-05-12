<?php

namespace In2code\In2publishCore\Component\TcaHandling\Demand;

use In2code\In2publishCore\Domain\Model\Node;
use In2code\In2publishCore\Domain\Model\Record;

class Demands
{
    private const RECORD_KEY_DELIMITER = '\\';

    private array $select = [];
    private array $join = [];
    private array $files = [];

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
        $this->select[$from][$additionalWhere][$property][$value][$this->uniqueRecordKey($record)] = $record;
    }

    /**
     * @param string $table e.g. sys_file_storage
     * @param string $field e.g. uid
     * @param int|string $search e.g. 13
     * @return void
     */
    public function unsetSelect(string $table, string $field, $search): void
    {
        foreach ($this->select[$table] ?? [] as $additionalWhere => $properties) {
            foreach ($properties as $property => $values) {
                if ($property === $field) {
                    unset($this->select[$table][$additionalWhere][$property][$search]);
                }
                if (empty($this->select[$table][$additionalWhere][$property])) {
                    unset($this->select[$table][$additionalWhere][$property]);
                }
            }
            if (empty($this->select[$table][$additionalWhere])) {
                unset($this->select[$table][$additionalWhere]);
            }
        }
        if (empty($this->select[$table])) {
            unset($this->select[$table]);
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
        $this->join[$mmTable][$joinTable][$additionalWhere][$property][$value][$uniqueRecordKey] = $record;
    }

    /**
     * @param string $mmTable e.g. tx_news_domain_model_news_related_mm
     * @param string $joinTable e.g. tx_news_domain_model_news
     * @param string $field e.g. uid_local
     * @param int|string $search e.g. 25
     * @return void
     */
    public function unsetJoin(string $mmTable, string $joinTable, string $field, $search): void
    {
        foreach ($this->join[$mmTable][$joinTable] ?? [] as $additionalWhere => $properties) {
            foreach ($properties as $property => $values) {
                if ($property === $field) {
                    unset($this->join[$mmTable][$joinTable][$additionalWhere][$property][$search]);
                }
                if (empty($this->join[$mmTable][$joinTable][$additionalWhere][$property])) {
                    unset($this->join[$mmTable][$joinTable][$additionalWhere][$property]);
                }
            }
            if (empty($this->join[$mmTable][$joinTable][$additionalWhere])) {
                unset($this->join[$mmTable][$joinTable][$additionalWhere]);
            }
        }
        if (empty($this->join[$mmTable][$joinTable])) {
            unset($this->join[$mmTable][$joinTable]);
        }
        if (empty($this->join[$mmTable])) {
            unset($this->join[$mmTable]);
        }
    }

    public function addFile(int $storage, string $identifier, Record $record): void
    {
        $this->files[$storage][$identifier][$this->uniqueRecordKey($record)] = $record;
    }

    /**
     * @return array<int, array<string, array<string, Record>>>
     */
    public function getFiles(): array
    {
        return $this->files;
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
        return $this->select;
    }

    /**
     * @return array<string, array<string, array<string, array<string, array<mixed, array<string, Node>>>>>>
     */
    public function getJoin(): array
    {
        return $this->join;
    }
}
