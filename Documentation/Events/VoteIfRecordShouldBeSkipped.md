# FolderInstanceWasCreated

Replaces the `\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldSkipRecord` Signal.

Refer to [Voting Events](Voting-Events.md) for general information about this kind of event.

## When

This event is created for each record which is going to be published. This means that this event can be fired thousands
of times during a single publishing process, depending on the number of records on your page.

## What

* `commonRepository`: The instance of the CommonRepository which is going to publish the record
* `record`: The record instance which is going to be published (if not voted otherwise).

## Possibilities

You can listen on this event and vote if the record should be actually be published or skipped.

### Example

Vote for skip:

```php
use In2code\In2publishCore\Event\VoteIfRecordShouldBeSkipped;

class RecordShouldBeSkippedVoter
{
    public function __invoke(VoteIfRecordShouldBeSkipped $event): void
    {
        if ($this->yourDecisionLogic()) {
            $event->voteYes();
        }
    }
}
```
