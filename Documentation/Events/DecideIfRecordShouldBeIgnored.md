# DecideIfRecordShouldBeIgnored

Replaces the `\In2code\In2publishCore\Controller\ToolsController / collectSupportPlaces` Signal.
Replaces the event `VoteIfRecordShouldBeIgnored`.

## When

Every time a new record which is not a `PageTreeRootRecord` was created.

## What

* `record`: The record that was just created and should be checked.

## Possibilities

You can vote to ignore the record completely. The record will not be attached to the record tree. Your event listener
might get asked about the same record multiple times. If an event listener call `shouldIgnore` no further event listener
will be asked.

### Example

```php
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Event\DecideIfRecordShouldBeIgnored;

class RecordIgnoreListener
{
    public function __invoke(DecideIfRecordShouldBeIgnored $event)
    {
        $record = $event->getRecord();
        if (
            $record instanceof FileRecord
            && $record->getProp('file_extension') === 'bak'
        ) {
            $event->shouldIgnore();
        }
    }
}
```
