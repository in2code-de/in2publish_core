# VoteIfFindingByIdentifierShouldBeSkipped

Replaces the `\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldSkipFindByIdentifier` Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

This event is dispatched before the content publisher queries the local and foreign database for a specific record.

## What

* `commonRepository`: The instance of the CommonRepository which is going to be used to fetch the rows from the
  databases.
* `identifier`: The identifier (uid) of the rows which are requested.
* `tableName`: The table where the row(s) will be fetched from.

## Possibilities

This voting event allows you to skip the queries to the local and foreign database. When skipped, the rows won't be
selected and no record will be instantiated for those rows. No related record will be resolved for the skipped record.

### Example

```php
use In2code\In2publishCore\Event\VoteIfFindingByIdentifierShouldBeSkipped;

class FindingByIdentifierShouldBeSkippedVoter
{
    public function __invoke(VoteIfFindingByIdentifierShouldBeSkipped $event): void
    {
        if (
            'tx_myextension_domain_model_something' === $event->getTableName()
            && in_array($event->getIdentifier(), $this->identifiersToSkip)
        ) {
            // Do not fetch the rows!
            $event->voteYes();
        }
    }
}
```
