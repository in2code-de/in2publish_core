# RecordWasPublished

## When

This event will be seen each time a single record was published.

**It is not guaranteed, that the record was actually written to the database or file system yet**, since most record
publishers are transactional and will write the record to the target location when the publishing process ends. If one
or more publisher fail, the publishing process will try to roll back everything. You should wait for
the [`RecursiveRecordPublishingEnded` (link)](RecursiveRecordPublishingEnded.md) event until you take action.

## What

* `record`: The record instance which was published.

## Possibilities

This event is intended to be used as a trigger for additional publishing actions, like the
`PhysicalFilePublisherEventListener`, which triggers the publishing of the actual disk file.

### Example

have a look at the class `In2code\In2publishCore\Features\RefIndexUpdate\Domain\Anomaly\RefIndexUpdater` for a
real-world example.

To have an event which is triggered after the complete publication of the dataset, `TaskExecutionWasFinished` should be
used.
