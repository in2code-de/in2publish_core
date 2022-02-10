# VoteIfRecordShouldBeIgnored

Replaces the `\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldIgnoreRecord` Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

This event is created for each set of rows before they are converted to a record instance. This means that this event
can be fired thousands of times during the record tree building process, depending on the number of records on your
page. The record tree building process takes place when the Publish Overview Module was opened or a record is going to
be published.

## What

* `recordFinder`: The instance of the `RecordFinder` which was used to fetch the rows.
* `localProperties`: The row from the local database as array.
* `foreignProperties`: The row from the foreign database as array.
* `tableName`: The table name where the rows are fetched from.

## Possibilities

You can vote to ignore specific records in the content publisher. When a record is going to be ignored, there won't be
any record instantiation and no related records will be searched for this set of rows. You could decide based on the
combination of the table name and row values.

### Example

Vote to skip where a local row exists and has no value or a value greater than 5 set in the field `myField`:

```php
use In2code\In2publishCore\Event\VoteIfRecordShouldBeIgnored;

class RecordShouldBeIgnoredVoter
{
    public function __invoke(VoteIfRecordShouldBeIgnored $event): void
    {
        $localProperties = $event->getLocalProperties();
        if (empty($localProperties['myField']) || $localProperties['myField'] > 5) {
            $event->voteYes();
        }
    }
}
```
