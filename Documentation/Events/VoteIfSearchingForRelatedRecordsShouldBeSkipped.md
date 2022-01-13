# VoteIfSearchingForRelatedRecordsShouldBeSkipped

Replaces the `\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldSkipSearchingForRelatedRecords` Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

This event is fired each time a record (content or page) is going to have its related records searched and added.

## What

* `recordFinder`: The instance of the `RecordFinder` which is going to be used to search for records on the page record.
* `record`: The current page record which is going to have its related records by PID added.
* `tableName`: The table which is going to be queried.

## Possibilities

You can use this voting event to completely skip searching for related records by TCA. If you only want to skip a
certain relation, you can use the
[`VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped` (link)](VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped.md)
voting event instead.

### Example

```php
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsShouldBeSkipped;

class SearchingForRelatedRecordsShouldBeSkippedVoter
{
    public function __invoke(VoteIfSearchingForRelatedRecordsShouldBeSkipped $event): void
    {
        $record = $event->getRecord();
        if (true === (bool)$record->getLocalProperty('tx_cpext_skip_relations')) {
            $event->voteYes();
        }
    }
}
```
