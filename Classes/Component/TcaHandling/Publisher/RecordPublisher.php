<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\Record;

use function array_diff_assoc;

class RecordPublisher
{
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

        $updates = [];
        $inserts = [];
        $deletes = [];

        foreach ($flatFlatRecords as $flatRecord) {
            $id = $flatRecord->getId();
            $table = $flatRecord->getClassification();
            $localProps = $flatRecord->getLocalProps();
            $foreignProps = $flatRecord->getForeignProps();

            if ($flatRecord->getState() === Record::S_ADDED) {
                $inserts[$table][] = $localProps;
                continue;
            }

            if ($flatRecord->getState() === Record::S_DELETED) {
                $deletes[$table][] = $id;
                continue;
            }

            $newValues = array_diff_assoc($localProps, $foreignProps);
            if (empty($newValues)) {
                $a = 'v';
            }

            $updates[$table][$id] = $newValues;
        }

        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump([$updates, $inserts, $deletes],
            __FILE__ . '@' . __LINE__,
            20,
            false,
            true,
            false,
            [],
            []);
        die();
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
        foreach ($record->getChildren() as $children) {
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
