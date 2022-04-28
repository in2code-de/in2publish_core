<?php

namespace In2code\In2publishCore\Component\TcaHandling;

use ArrayAccess;
use ArrayIterator;
use In2code\In2publishCore\Domain\Model\Record;
use Iterator;
use IteratorAggregate;

use function array_key_exists;
use function array_keys;
use function is_array;

class RecordCollection implements IteratorAggregate, ArrayAccess
{
    private array $records = [];

    /**
     * @param array<Record>|array<array<Record>> $records
     */
    public function __construct(array $records = [])
    {
        $this->addRecords($records);
    }

    public function addRecord(Record $record): void
    {
        $this->records[$record->getClassification()][$record->getId()] = $record;
    }

    /**
     * @param array<Record>|array<array<Record>> $records
     * @return void
     */
    public function addRecords(array $records): void
    {
        foreach ($records as $record) {
            if (is_array($record)) {
                $this->addRecords($record);
            } else {
                $this->addRecord($record);
            }
        }
    }

    public function addRecordCollection(RecordCollection $recordCollection): void
    {
        $this->addRecords($recordCollection->records);
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function getRecordsFlat(): array
    {
        $records = array_values($this->records);
        return array_merge([], ...$records);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->getRecordsFlat());
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->records);
    }

    /**
     * @param $offset
     * @return array<int|string, Record>
     */
    public function offsetGet($offset): array
    {
        return $this->records[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->records[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->records[$offset]);
    }

    public function getRecord(string $classification, $id): ?Record
    {
        return $this->records[$classification][$id] ?? null;
    }

    /**
     * @return array<string>
     */
    public function getClassifications(): array
    {
        return array_keys($this->records);
    }
}
