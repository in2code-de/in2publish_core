# RootRecordCreationWasFinished

Replaces the `\In2code\In2publishCore\Domain\Factory\RecordFactory / rootRecordFinished` Signal.

## When

This event will be fired once after the central record tree building process ended. It marks the finish of all "normal"
processes which ran to build the first record instance, including all related records. It will also be dispatched in
the "root record simulation mode", which allows related records to get instantiated without an existing parent record.
This internal feature is used for the Publish Files Module, which will trigger this event.

## What

* `recordFactory`: The instance of the record factory that created the instance of the root record.
* `record`: The first record instance which was created and was just finished.

## Possibilities

This event marks the end of the normal process, which is a good place to amend the record tree with your own records,
add additional properties, or - but not limited to - collecting statistics.
