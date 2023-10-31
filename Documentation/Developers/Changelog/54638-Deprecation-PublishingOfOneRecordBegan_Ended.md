# Deprecation: PublishingOfOneRecordBegan/Ended Event

Issue https://projekte.in2code.de/issues/54638

## Description

The events

* `\In2code\In2publishCore\Event\PublishingOfOneRecordBegan`
* `\In2code\In2publishCore\Event\PublishingOfOneRecordEnded`

have changed behavior, also they are so close to each other in their function and name, that they have been merged into
the event [`RecordWasPublished` (link)](../Events/RecordWasPublished.md). Another
event, [`RecordWasSelectedForPublishing` (link)](../Events/RecordWasSelectedForPublishing.md), was introduced to replace
the original behavior.

## Impact

Follwing events are deprecated and will be removed in version 13:

* `\In2code\In2publishCore\Event\PublishingOfOneRecordBegan`
* `\In2code\In2publishCore\Event\PublishingOfOneRecordEnded`

## Affected Installations

All.

## Migration

Use `\In2code\In2publishCore\Event\RecordWasPublished` or `\In2code\In2publishCore\Event\RecordWasSelectedForPublishing`
depending on your use case instead.
See [`RecordWasSelectedForPublishing` (link)](../Events/RecordWasSelectedForPublishing.md) for a comparison which event
is dispatched on which case.
