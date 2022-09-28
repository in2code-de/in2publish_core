# RecordWasCreated

Replaces the `\In2code\In2publishCore\Controller\ToolsController / collectSupportPlaces` Signal.
Replaces the events `RecordInstanceWasInstantiated` and `FolderInstanceWasCreated`

## When

Every time a new record was created.

## What

* `record`: The record that was just created.

## Possibilities

This event will be dispatched after `DecideIfRecordShouldBeIgnored` if the record is not ignored. You can use this event
to react on any single record that was created.

If you want to react on all newly created record objects in a batch operation, you can
use [`DemandsWereResolved` (link)](DemandsWereResolved.md) instead.

### Example

```php
use In2code\In2publishCore\Event\RecordWasCreated;

class RecordCreationListener
{
    public function __invoke(RecordWasCreated $event)
    {
        $record = $event->getRecord();
        echo $record->getClassification() . ' [' . $record->getIdentifier() . '] created';
    }
}
```
