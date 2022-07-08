<?php

namespace In2code\In2publishCore\Component\TcaHandling;

use Generator;
use In2code\In2publishCore\Domain\Model\Record;

use function array_keys;
use function is_array;

/**
 * This should be a WeakMap but that's available only in PHP 8, but we have to support 7.4.
 */
class RecordCollection
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
     * @return void
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
}
