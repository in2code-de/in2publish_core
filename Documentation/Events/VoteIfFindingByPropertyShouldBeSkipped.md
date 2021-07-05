# VoteIfFindingByPropertyShouldBeSkipped

Replaces the `\In2code\In2publishCore\Domain\Repository\CommonRepository / VoteIfFindingByPropertyShouldBeSkipped`
Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

This event will be seen once each time before the `CommonRepository` is going to search for a single record by a single
property **AND** once for each property when searching for records by multiple properties.

This can result in thousands of events of this type, depending on the number of records on your page and relations in
your TCA.

## What

* `commonRepository`: The instance of the common repository that is going to query the databases.
* `propertyName`: The property name which will be used as field name in the query.
* `propertyValue`: The value which the database row has to match in the `propertyName` column.
* `tableName`: The table name the query will select from.

## Possibilities

With this voting event you can skip nearly any related record resolving. Keep in mind that the event will be created a
lot of times, and you should not do time or resource intensive computing for your decision or else you will massively
degrade the Content Publisher's performance.

### Example

Given you want to skip searching for all records with a specific field value you can accomplish it by the following
example:

```php
use In2code\In2publishCore\Event\VoteIfFindingByPropertyShouldBeSkipped;

class RecordShouldBeIgnoredVoter
{
    public function __invoke(VoteIfFindingByPropertyShouldBeSkipped $event): void
    {
        if ('tx_myext_someproperty' === $event->getPropertyName() && true === (bool)$event->getPropertyValue()) {
            // Skip searching for records where the tx_myext_someproperty field contains a treu-ish value
            $event->voteYes();
        }
    }
}
```
