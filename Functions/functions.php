<?php

namespace In2code\In2publishCore {

    use In2code\In2publishCore\Domain\Model\Record;

    use function array_values;
    use function function_exists;

    if (!function_exists('\In2code\In2publishCore\merge_record')) {
        /**
         * @param array<string, array<int|string, Record>> $list
         * @param Record $record
         */
        function merge_record(array &$list, Record $record): void
        {
            $list[$record->getClassification()][$record->getId()] = $record;
        }
    }

    if (!function_exists('\In2code\In2publishCore\merge_records')) {
        /**
         * @param array<string, array<int|string, Record>> $list
         * @param array<string, array<int|string, Record>> $records
         */
        function merge_records(array &$list, array $records): void
        {
            if (empty($list)) {
                $list = $records;
                return;
            }
            foreach ($records as $table => $recordsPerTable) {
                foreach ($recordsPerTable as $id => $recordFromTable) {
                    $list[$table][$id] = $recordFromTable;
                }
            }
        }
    }

    if (!function_exists('\In2code\In2publishCore\flatten_records')) {
        /**
         * @param array<string, array<int|string, Record>> $list
         * @return list<Record>
         */
        function flatten_records(array $records): array
        {
            $return = [];
            foreach ($records as $recordList) {
                foreach ($recordList as $record) {
                    $return[] = $record;
                }
            }
            return $return;
        }
    }
}
