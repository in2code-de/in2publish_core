<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling;

use In2code\In2publishCore\Domain\Model\Record;

class TempRecordIndex
{
    private $records = [];

    public function addRecord(Record $record): void
    {
        $this->records[$record->getClassification()][$record->getId()] = $record;
    }

    /**
     * @param array<Record> $records
     */
    public function addRecords(array $records): void
    {
        foreach ($records as $record) {
            $this->addRecord($record);
        }
    }

    /**
     * @param array-key $id
     */
    public function getRecord(string $classification, $id): ?Record
    {
        return $this->records[$classification][$id] ?? null;
    }

    public function getRecordByClassification(string $classification): array
    {
        return $this->records[$classification] ?? [];
    }
}
