# RecursiveRecordPublishingBegan

Replaces the `\In2code\In2publishCore\Repository\CommonRepository / publishRecordRecursiveBegin` Signal.

## When

This event will be fired when the publishing process has begun. It identifies the beginning of the process and will be
dispatched once. The end of the publishing process will be identified by the
[`RecursiveRecordPublishingEnded` (link)](RecursiveRecordPublishingEnded.md) event, if no uncatched exception was
thrown.

## What

* `recordTree`: The `RecordTree` which contains all related records which are going to be published.

## Possibilities

Since this event designates the beginning of the publishing process, it is the last possibility to change the full
record tree, traverse it to scan for something specific or stop the process by throwing an exception. If you want to act
on a specific record before it is going to be published you should use the
[`PublishingOfOneRecordBegan` (link)](PublishingOfOneRecordBegan.md) event instead. If you want to act on a specific
record _after_ it got published you should use the [`PublishingOfOneRecordEnded` (link)](PublishingOfOneRecordEnded.md)
event.

### Example

This (bad) example shows how to stop the publishing process when there are more than 18 records with a specific value.

```php
<?php

use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;

class SomeFieldNamePublishingBlocker
{
    public function onPhysicalFileWasPublished(RecursiveRecordPublishingBegan $event): void
    {
        $count = 0;
        $recordTree = $event->getRecordTree();
        foreach ($recordTree->getChildren() as $records) {
            foreach ($records as $record) {
                $count += $this->countRecordsRecursiveWhichHaveSomeValue($record)
            }
        }
        if ($count > 18) {
            throw new Exception('You must not publish more than 18 records which have "somefieldvalue"');
        }
    }

    protected function countRecordsRecursiveWhichHaveSomeValue(Record $record): int
    {
        $count = 0;
        if ($record->getProp('somefieldname') === 'somefieldvalue') {
            $count++;
        }
        foreach ($record->getChildren() as $records) {
            foreach ($records as $childRecord) {
                $count += $this->countRecordsRecursiveWhichHaveSomeValue($childRecord);
            }
        }
        return $count;
    }
}
```
