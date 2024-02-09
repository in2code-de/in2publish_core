<?php

namespace In2code\In2publishCore\Component\Core;

use Closure;
use Generator;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use Iterator;
use IteratorAggregate;
use NoRewindIterator;

use function array_keys;
use function is_array;

/**
 * This should be a WeakMap but that's available only in PHP 8, but we have to support 7.4.
 */
class RecordCollection implements IteratorAggregate
{
    /**
     * @var array<string, array<array-key, Record>>
     */
    private array $records = [];

    /**
     * @param iterable<Record>|iterable<array<Record>> $records
     */
    public function __construct(iterable $records = [])
    {
        $this->addRecords($records);
    }

    public function addRecord(Record $record): void
    {
        $this->records[$record->getClassification()][$record->getId()] = $record;
    }

    /**
     * @param iterable<Record>|iterable<array<Record>> $records
     */
    public function addRecords(iterable $records): void
    {
        foreach ($records as $record) {
            if (is_array($record)) {
                $this->addRecords($record);
            } else {
                $this->addRecord($record);
            }
        }
    }

    /**
     * @return array<Record>
     */
    public function getRecords(string $classification = null): array
    {
        if (null === $classification) {
            return $this->records;
        }
        return $this->records[$classification] ?? [];
    }

    /**
     * @param array-key $id
     */
    public function getRecord(string $classification, $id): ?Record
    {
        return $this->records[$classification][$id] ?? null;
    }

    /**
     * @return Generator<Record>
     */
    public function getRecordsFlat(): Generator
    {
        foreach ($this->records as $identifiers) {
            foreach ($identifiers as $identifier => $record) {
                yield $identifier => $record;
            }
        }
    }

    /**
     * @return array<string>
     */
    public function getClassifications(): array
    {
        return array_keys($this->records);
    }

    public function isEmpty(): bool
    {
        return empty($this->records);
    }

    public function contains(string $classification, array $properties): bool
    {
        return null !== $this->getFirstRecordByProperties($classification, $properties);
    }

    public function getFirstRecordByProperties(string $classification, array $properties): ?Record
    {
        if (isset($properties['uid'])) {
            return $this->getRecord($classification, $properties['uid']);
        }
        foreach ($this->records[$classification] as $record) {
            foreach ($properties as $property => $value) {
                if ($record->getProp($property) !== $value) {
                    continue 2;
                }
            }
            return $record;
        }
        return null;
    }

    /**
     * @return array<Record>
     */
    public function getRecordsByProperties(string $classification, array $properties): array
    {
        if (isset($properties['uid'])) {
            $record = $this->getRecord($classification, $properties['uid']);
            if (null !== $record) {
                return [$record];
            }
            return [];
        }
        $records = [];
        foreach ($this->records[$classification] as $record) {
            foreach ($properties as $property => $value) {
                if ($record->getProp($property) !== $value) {
                    continue 2;
                }
            }
            $records[] = $record;
        }
        return $records;
    }

    public function map(Closure $closure): array
    {
        $return = [];
        foreach ($this->getRecordsFlat() as $record) {
            $return[] = $closure($record);
        }
        return $return;
    }

    public function are(Closure $closure): bool
    {
        foreach ($this->getRecordsFlat() as $record) {
            if (!$closure($record)) {
                return false;
            }
        }
        return true;
    }

    public function getIterator(): Iterator
    {
        return new NoRewindIterator($this->getRecordsFlat());
    }
}
