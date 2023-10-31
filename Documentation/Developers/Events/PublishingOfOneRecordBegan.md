# PublishingOfOneRecordBegan

**Deprecated. Use the event [`RecordWasPublished` (link)](RecordWasPublished.md) instead. This event will be removed in
version 13.0**

Replaces the `\In2code\In2publishCore\Repository\CommonRepository / publishRecordRecursiveBeforePublishing` Signal.

## When

This event will be seen each time a single record will be published. It is dispatched right after
[`VoteIfRecordShouldBeSkipped` (link)](VoteIfRecordShouldBeSkipped.md), which is used to decide if the record should be
published or not.

## What

* `record`: The record instance which will be published.

## Possibilities

This event can be used (and is used internally) to collect a list of records, which are actually published. These lists
will later be used to create Tasks for those records.

### Example

Real world examples are the best examples. Have a look at the
`\In2code\In2publishCore\Features\NewsSupport\EventListener\NewsSupportEventListener`, which is a simple event listener
that delegates the domain logic to the `NewsCacheInvalidator`, which collects all published records.

_(This file will be remove in version 13)_
