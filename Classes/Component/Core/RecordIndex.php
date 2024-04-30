<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core;

use In2code\In2publishCore\Component\Core\Record\Model\Record;

class RecordIndex
{
    /**
     * @var RecordCollection<int, Record>
     */
    private RecordCollection $records;

    public function __construct()
    {
        $this->records = new RecordCollection();
    }

    public function addRecord(Record $record): void
    {
        $this->records->addRecord($record);
    }

    /**
     * @return array<Record>
     */
    public function getRecords(string $classification = null): array
    {
        return $this->records->getRecords($classification);
    }

    /**
     * @param array-key $id
     */
    public function getRecord(string $classification, $id): ?Record
    {
        return $this->records->getRecord($classification, $id);
    }
}
