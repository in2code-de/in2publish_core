# VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped

Replaces the `\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldSkipSearchingForRelatedRecordByTable`
Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

This event is like `VoteIfPageRecordEnrichingShouldBeSkipped` but instead of once per page record it is fired once per
page record and per table which potentially has records stored on that page record.

## What

* `recordFinder`: The instance of the `RecordFinder` which is going to be used to search for records on the page record.
* `record`: The current page record which is going to have its related records by PID added.
* `tableName`: The table which is going to be queried.

## Possibilities

Since this event will be dispatched for each page record per table you can vote to skip the search of related records by
PID per table and page record. A performance feature of the content publisher itself use this event to skip tables where
no record will be found for the given page record.

### Example

The best examples are real-world examples. Have a look at the
`\In2code\In2publishCore\Features\SkipEmptyTable\SkipTableVoter` to see how it works.
