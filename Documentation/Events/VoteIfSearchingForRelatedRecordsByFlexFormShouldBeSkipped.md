# VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped

Replaces
the `\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldSkipSearchingForRelatedRecordsByFlexForm`
Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

This event will be triggered each time the Content Publisher identified a TCA type `flex` field and resolved the
FlexForm configuration and data, but before the related records are going to be searched.

FlexForms define TCA which is dynamically embedded into a record based on the records properties, which makes it a
dynamic version of the TCA. Hence, the Content Publisher can not pre-process the FlexForms and trigger the
event `VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped` which would be used for "normal" TCA relations.

## What

* `commonRepository`: The instance of the CommonRepository which is going to be used to search for records.
* `record`: The current record which is going to have its related records by FlexForm added.
* `column`: The name of the column of the current record which is configured with type `flex`.
* `columnConfiguration`: The TCA `columns` configuration for the `column`.
* `flexFormDefinition`: The FlexForm, converted into a processable array.
* `flexFormData`: The data from the database, processed into an array by the given `flexFormDefinition`.

## Possibilities

You can skip certain relations defined in FlexForms by voting "Yes" in your EventListener.

### Example

This example shows how to skip relation resolving by FlexForm for a EXT:News plugin.

```php
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped;

class RecordShouldBeIgnoredVoter
{
    public function __invoke(VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped $event): void
    {
        $record = $event->getRecord();
        if (
            'tt_content' === $record->getTableName()
            && 'pi_flexform' === $event->getColumn()
            && 'list' === ($record->getLocalProperty('CType') ?? $record->getForeignProperty('CType'))
            && 'news_pi1' === ($record->getLocalProperty('list_type') ?? $record->getForeignProperty('list_type'))
        ) {
            $event->voteYes();
        }
    }
}
```
