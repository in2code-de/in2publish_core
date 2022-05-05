# VoteIfPageRecordEnrichingShouldBeSkipped

Replaces the `\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldSkipEnrichingPageRecord` Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

Each time a record instance from the pages table was created, the `RecordFactory` will search in all tables for records
that are stored on that page. This event is created right in between the instantiation and the table querying.

## What

* `recordFinder`: The instance of the `RecordFinder` which will be used to find related records.
* `record`: The page record instance.

## Possibilities

Voting for yes will skip searching for records in **all tables** _based on their pid_. The normal record relation
resolving based on TCA will not be skipped.

### Example

Vote "Yes" to skip all PID-based record relation resolver.

```php
use In2code\In2publishCore\Event\VoteIfPageRecordEnrichingShouldBeSkipped;

class PageRecordEnrichingShouldBeSkippedVoter
{
    public function __invoke(VoteIfPageRecordEnrichingShouldBeSkipped $event): void
    {
        $record = $event->getRecord();
        if ($record->getPageIdentifier() > 9000) {
            // Do not resolve PID-based relations for pages with UID higher than 9000 (bad example!)
            $event->voteYes();
        }
    }
}
```
