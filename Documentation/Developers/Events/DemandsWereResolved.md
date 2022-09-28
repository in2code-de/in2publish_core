# DemandsWereResolved

## When

Right after a demands object was resolved.

## What

* `demands`: The demands object that contains all structured select, join, and sys_redirect queries as structured array.
* `recordCollection`: The record collection which contains all **new** records found. Records which already existed in
  the record index are not included.

## Possibilities

You can act on all newly found records in batch. To act on each single record object creation listen
to [`RecordWasCreated` (link)](RecordWasCreated.md) instead.

### Example

```php
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Event\DemandsWereResolved;

class BatchRecordCreatedListener
{
    public function __invoke(DemandsWereResolved $event)
    {
        $recordCollection = $event->getRecordCollection();
        $areAllMoved = $recordCollection->are(
            static fn(Record $record): bool => $record->getState() === Record::S_MOVED
        );
        if ($areAllMoved) {
            // Add a flash message
        }
    }
}
```
