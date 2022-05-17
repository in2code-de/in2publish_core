# RecordWasSelectedForPublishing

Replaces the `\In2code\In2publishCore\Controller\RecordController / beforePublishing` Signal.

## When

This event will be dispatched when a record in the Publish Overview Module has been selected for publishing.

## What

* `recordController`: The record controller which was used to execute the request.
* `record`: A fully resolved instance of the record.

## Possibilities

You can inspect/alter the record. If you want to listen for records that will be published regardless of the view where
they were selected you can use [`RecursiveRecordPublishingBegan` (link)](RecursiveRecordPublishingBegan.md) instead.
