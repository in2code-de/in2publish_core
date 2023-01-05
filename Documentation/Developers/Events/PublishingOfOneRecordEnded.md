# PublishingOfOneRecordEnded

**Deprecated. Use the event [`RecordWasPublished` (link)](RecordWasPublished.md) instead. This event will be removed in version 13.0**

Replaces the `\In2code\In2publishCore\Repository\CommonRepository / publishRecordRecursiveEnd` Signal.

## When

This event will be seen each time a single record was published. It is not guaranteed, that the record was actually
written to the database or file system yet, since most record publishers are transactional and will write the record to
the target location when the publishing process ends.

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

_(This file will be remove in version 13)_
