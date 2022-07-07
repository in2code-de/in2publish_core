<?php

namespace In2code\In2publishCore\Component\TcaHandling;

use Generator;
use In2code\In2publishCore\Domain\Model\Record;
use WeakReference;

use function array_keys;
use function is_array;

/**
 * This should be a WeakMap but that's available only in PHP 8, but we have to support 7.4.
 */
class RecordCollection
{
    /**
     * @var array<string, array<array-key, WeakReference>>
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
        $this->records[$record->getClassification()][$record->getId()] = WeakReference::create($record);
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
     * @return Generator<Record>
     */
    public function getRecordsByClassification(string $classification): Generator
    {
        foreach ($this->records[$classification] ?? [] as $identifier => $weakReference) {
            $record = $weakReference->get();
            if (null === $record) {
                unset($this->records[$classification][$identifier]);
                if (empty($this->records[$classification])) {
                    unset($this->records[$classification]);
                }
            } else {
                yield $identifier => $record;
            }
        }
    }

    /**
     * @return Generator<Record>
     */
    public function getRecordsFlat(): Generator
    {
        foreach ($this->records as $classification => $identifiers) {
            foreach ($identifiers as $identifier => $weakReference) {
                $record = $weakReference->get();
                if (null === $record) {
                    unset($this->records[$classification][$identifier]);
                    if (empty($this->records[$classification])) {
                        unset($this->records[$classification]);
                    }
                } else {
                    yield $identifier => $record;
                }
            }
        }
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
        $this->filterRecords();
        return array_keys($this->records);
    }

    public function isEmpty(): bool
    {
        $this->filterRecords();
        return empty($this->records);
    }

    protected function filterRecords(): void
    {
        foreach ($this->records as $classification => $identifiers) {
            foreach ($identifiers as $identifier => $weakReference) {
                $record = $weakReference->get();
                if (null === $record) {
                    unset($this->records[$classification][$identifier]);
                    if (empty($this->records[$classification])) {
                        unset($this->records[$classification]);
                    }
                }
            }
        }
    }
}
