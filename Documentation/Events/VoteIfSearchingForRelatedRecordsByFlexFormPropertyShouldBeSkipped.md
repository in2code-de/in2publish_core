# VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped

Replaces the
`\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldSkipSearchingForRelatedRecordsByFlexFormProperty`
Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

This event will be dispatched if the preceding voting event `VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped`
did not result in the skipping of the FlexForm relations. Since the FlexForm can not be pre-processed (see
[this explanation](VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped.md#When)), the Content Publisher will fire
this event for all FlexForm fields, regardless if they define a relation to another record or not.

## What

* `recordFinder`: The instance of the `RecordFinder` which is going to be used to search for the records.
* `record`: The current record which is going to have its related records by FlexForm added.
* `column`: The name of the column of the current record which is configured with type `flex`.
* `key`: The name of the FlexForm field which is going to be looked at for relation resolving.
* `config`: The FlexForm configuration of the FlexForm field `key`.
* `flexFormData`: The FlexForm data from the current record, processed and ready to be used. This is the value which was
  entered in the FlexForm in the Backend and is most likely a string.

## Possibilities

Voting "Yes" on this event will skip searching for related records based on the current FlexForm `key` field (or rather
by the `config`, which is a relation defined in TCA-style).

You do not need to ignore FlexForm fields which do not define a relation. The Content Publisher will not try to resolve
relations where none exist.

This event can be compared to
[`VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped`](VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped.md)
as this is fired for FlexForm field defined relations , and the other for TCA column defined relations.

### Example

This example shows you how to ignore a FlexForm field named `cdnImages` from the FlexForm configuration of
your `myext_pi1` plugin.

```php
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped;

class SearchingForRelatedRecordsByFlexFormPropertyShouldBeSkippedVoter
{
    public function __invoke(VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped $event): void
    {
        $record = $event->getRecord();
        if (
            'tt_content' === $record->getTableName()
            && 'cdnImages' === $event->getKey()
            && 'pi_flexform' === $event->getColumn()
            && 'list' === ($record->getLocalProperty('CType') ?? $record->getForeignProperty('CType'))
            && 'myext_pi1' === ($record->getLocalProperty('list_type') ?? $record->getForeignProperty('list_type'))
        ) {
            $event->voteYes();
        }
    }
}
```
