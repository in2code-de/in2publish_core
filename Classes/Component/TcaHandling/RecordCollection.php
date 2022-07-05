<?php

namespace In2code\In2publishCore\Component\TcaHandling;

use ArrayIterator;
use In2code\In2publishCore\Domain\Model\Record;
use Iterator;
use IteratorAggregate;
use WeakReference;

use function array_keys;
use function array_merge;
use function array_values;
use function is_array;

class RecordCollection implements IteratorAggregate
{
    /**
     * @var array<string, array<array-key, WeakReference>>
     */
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
        $this->records[$record->getClassification()][$record->getId()] = WeakReference::create($record);
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

    private function filterReferences(): array
    {
        $records = [];
        foreach ($this->records as $classification => $identifiers) {
            foreach ($identifiers as $identifier => $weakReference) {
                $record = $weakReference->get();
                if (null === $record) {
                    unset($this->records[$classification][$identifier]);
                    if (empty($this->records[$classification])) {
                        unset($this->records[$classification]);
                    }
                } else {
                    $records[$classification][$identifier] = $record;
                }
            }
        }
        return $records;
    }

    public function getRecords(): array
    {
        return $this->filterReferences();
    }

    /**
     * @return array<Record>
     */
    public function getRecordsFlat(): array
    {
        $records = array_values($this->getRecords());
        return array_merge([], ...$records);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->getRecordsFlat());
    }

    /**
     * @param array-key $id
     */
    public function getRecord(string $classification, $id): ?Record
    {
        $weakReference = $this->records[$classification][$id] ?? null;
        if (null === $weakReference) {
            return null;
        }
        /** @var Record $record */
        $record = $weakReference->get();
        if (null === $record) {
            unset($this->records[$classification][$id]);
        }
        return $record;
    }

    /**
     * @return array<string>
     */
    public function getClassifications(): array
    {
        return array_keys($this->getRecords());
    }

    public function isEmpty(): bool
    {
        return empty($this->records);
    }
}
