<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use In2code\In2publishCore\Component\TcaHandling\Repository\SingleDatabaseRepository;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\Record;

class RecordPublisher
{
    protected SingleDatabaseRepository $foreignRepository;

    public function injectForeignDatabase(SingleDatabaseRepository $foreignRepository): void
    {
        $this->foreignRepository = $foreignRepository;
    }

    public function publishRecord(Record $record): void
    {
        /** @var array<string, array<int, Record>> $records */
        $records = [];
        $this->recursiveRecordsToFlatArray($record, $records);
        $flatFlatRecords = $this->flatArrayToRecordList($records);
        foreach ($flatFlatRecords as $idx => $flatRecord) {
            if (
                $flatRecord->getState() === Record::S_UNCHANGED
                || !($flatRecord instanceof DatabaseRecord)
            ) {
                unset($flatFlatRecords[$idx]);
            }
        }

        $this->foreignRepository->publishRecordsToForeign($flatFlatRecords);
    }

    /**
     * @param array<string, array<int, Record>> $records
     */
    protected function recursiveRecordsToFlatArray(Record $record, array &$records = []): void
    {
        $table = $record->getClassification();
        $id = $record->getId();
        if (isset($records[$table][$id])) {
            return;
        }
        $records[$table][$id] = $record;
        foreach ($record->getChildren() as $table => $children) {
            if ('pages' === $table) {
                continue;
            }
            foreach ($children as $child) {
                $this->recursiveRecordsToFlatArray($child, $records);
            }
        }
    }

    /**
     * @param array<string, array<int, Record>>
     * @return list<Record>
     */
    protected function flatArrayToRecordList(array $records): array
    {
        $flatFlatRecords = [];
        foreach ($records as $ids) {
            foreach ($ids as $record) {
                $flatFlatRecords[] = $record;
            }
        }
        return $flatFlatRecords;
    }
}
