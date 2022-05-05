<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\Record;

use function array_diff_assoc;
use function array_values;

class RecordPublisher
{
    public function publishRecord(Record $record): void
    {
        $records = [];
        $this->getRecordsFlat($record, $records);
        $flatFlatRecords = [];
        foreach ($records as $ids) {
            foreach ($ids as $record) {
                $flatFlatRecords[] = $record;
            }
        }
        foreach ($flatFlatRecords as $idx => $record) {
            if (
                $record->getState() === Record::S_UNCHANGED
                || !($record instanceof DatabaseRecord)
            ) {
                unset($flatFlatRecords[$idx]);
            }
        }

        $updates = [];
        $inserts = [];
        $deletes = [];

        foreach ($flatFlatRecords as $record) {
            $id = $record->getId();
            $table = $record->getClassification();
            $localProps = $record->getLocalProps();
            $foreignProps = $record->getForeignProps();

            if ($record->getState() === Record::S_ADDED) {
                $inserts[$table][] = $localProps;
                continue;
            }

            if ($record->getState() === Record::S_DELETED) {
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

    protected function getRecordsFlat(Record $record, array &$records = []): void
    {
        $table = $record->getClassification();
        $id = $record->getId();
        if (isset($records[$table][$id])) {
            return;
        }
        $records[$table][$id] = $record;
        foreach ($record->getChildren() as $children) {
            foreach ($children as $child) {
                $this->getRecordsFlat($child, $records);
            }
        }
    }
}
