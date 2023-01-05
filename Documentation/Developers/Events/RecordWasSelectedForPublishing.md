# RecordWasSelectedForPublishing

## When

This event is dispatched for each single record of the record tree that is currently being published, regardless of its
state or if it is actually going to be published. The main difference between this event and the
events [`RecordWasPublished` (link)](RecordWasPublished.md) is, `RecordWasPublished` will not get dispatched when the
record is unchanged or ignored by vote.

This event is another replacement for the events `PublishingOfOneRecordBegan` and `PublishingOfOneRecordEnded` (renamed
to `RecordWasPublished` in v12.2) of version 11 and lower, because those events are not dispatched anymore if the record
is unchanged. There is, however, one remarkable difference. This event is dispatched also fore records which are
ignored, which is different to the other events in v11, which were not dispatched in case that the record was ignored.

If you want to know if the record was unchanged but ignored, you have to dispatch the
event [`CollectReasonsWhyTheRecordIsNotPublishable` (link)](CollectReasonsWhyTheRecordIsNotPublishable.md) yourself if
the record is unchanged or see if the event [`RecordWasPublished` (link)](RecordWasPublished.md) was **not** dispatched
for the record in question.

| Event dispatched in case y for version x | v11                                                           | >= v12.2                                                  |
|------------------------------------------|---------------------------------------------------------------|-----------------------------------------------------------|
| Record unchanged, ignored                |                                                               | `RecordWasSelectedForPublishing`                          |
| Record unchanged, not ignored            | `PublishingOfOneRecordBegan`<br/>`PublishingOfOneRecordEnded` | `RecordWasSelectedForPublishing`                          |
| Record changed, ignored                  |                                                               | `RecordWasSelectedForPublishing`                          |
| Record changed, not ignored (published)  | `PublishingOfOneRecordBegan`<br/>`PublishingOfOneRecordEnded` | `RecordWasSelectedForPublishing`<br/>`RecordWasPublished` |

## What

* `record`: The record instance which will be published.

## Possibilities

This event can be used (and is used internally) to collect a list of records, which are actually published. These lists
will later be used to create Tasks for those records.

### Example

Real world examples are the best examples. Have a look at the
`\In2code\In2publishCore\Features\NewsSupport\EventListener\NewsSupportEventListener`, which is a simple event listener
that delegates the domain logic to the `NewsCacheInvalidator`, which collects all published records.
