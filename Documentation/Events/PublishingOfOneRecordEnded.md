# PublishingOfOneRecordEnded

Replaces the `\In2code\In2publishCore\Repository\CommonRepository / publishRecordRecursiveEnd` Signal.

## When

This event will be seen each time a single record was published.

## What

* `record`: The record instance which was published.
* `commonRepository`: The instance of the CommonRepository which was used to publish the record.

## Possibilities

This event is intended to be used as a trigger for additional publishing actions, like the
`PhysicalFilePublisherEventListener`, which triggers the publishing of the actual disk file.

### Example

See the `\In2code\In2publishCore\Features\PhysicalFilePublisher\EventListener\PhysicalFilePublisherEventListener` for a
real-world example.
