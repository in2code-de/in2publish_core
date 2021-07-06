# AllRelatedRecordsWereAddedToOneRecord

Replaces the `\In2code\In2publishCore\Controller\FileController / addAdditionalRelatedRecords` Signal.

## When

Each time a record has all related records added. This event will be thrown for both page and content records.

## What

* `recordFactory`: The instance of the record factory that created all the records.
* `record`: The record which just has gotten all related records added.

## Possibilities

You can listen on this event to add additional related records to the record in the event. You can do everything else to
the record, but that is not quite the intention of this event.

### Example

This example shows how to add additional records:

```php
use In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord;

class AdditionRelationAdder
{
    public function __invoke(AllRelatedRecordsWereAddedToOneRecord $event): void
    {
        $record = $event->getRecord();
        if ('tx_myext_domain_model_something' === $record->getTableName()) {
            $relatedRecords = $this->fetchRelatedRecordsByRecord($record);
            $record->addRelatedRecord($relatedRecords);
        }
    }
}
```
