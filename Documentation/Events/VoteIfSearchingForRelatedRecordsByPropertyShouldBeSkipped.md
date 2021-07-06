# VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped

Replaces the
`\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldSkipSearchingForRelatedRecordsByProperty` Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

This event is fired for each record for each TCA column of that record's table which can hold a relation to another
record. This can result in thousands of events of this type being dispatched, depending on the number of records on your
page and the number of relations in the TCA.

This event does not occur for a record if the prior voting event
[`VoteIfSearchingForRelatedRecordsShouldBeSkipped`](VoteIfSearchingForRelatedRecordsShouldBeSkipped.md) resulted in the
skipping of this record.

## What

* `commonRepository`: The instance of the CommonRepository which is going to be used to search for related records.
* `record`: The current (page or content) record which is going to have its related records added.
* `propertyName`: The name of the column in the TCA of the record's table which is going to be resolved.
* `columnConfiguration`: The TCA of the column.

## Possibilities

Other than [`VoteIfSearchingForRelatedRecordsShouldBeSkipped`](VoteIfSearchingForRelatedRecordsShouldBeSkipped.md), this
event is fired for each column in the TCA of the record's table. Therefore, you have a fine-grained control whether a
specific relation of a specific record should be resolved or skipped.

This event can be compared to
[`VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped`](VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped.md)
as this is fired for TCA column defined relations, and the other for FlexForm field defined relations.

### Example

This example shows you how to always skip a field from a table, even if the field is configured to hold a relation.

```php
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped;

class RecordShouldBeIgnoredVoter
{
    public function __invoke(VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped $event): void
    {
        if (
            'a_field_to_skip' === $event->getPropertyName()
            && 'tx_myext_domain_model_something' === $event->getRecord()->getTableName()
        ) {
            $event->voteYes();
        }
    }
}
```
