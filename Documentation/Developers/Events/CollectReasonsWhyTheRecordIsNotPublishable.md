# CollectReasonsWhyTheRecordIsNotPublishable

Replaces the `\In2code\In2publishCore\Domain\Repository\CommonRepository / shouldSkipRecord` Signal.
Replaces the `VoteIfRecordShouldBeSkipped` event.

## When

This event is created for each record when the record is checked if it can be published. This means that this event can
be fired thousands of times during a single publishing process or when the Publish Overview Module, depending on the
number of records that you show or publish.

## What

* `record`: The record instance which is going to be published (if not voted otherwise).

## Possibilities

You can listen on this event and add reasons why the record must be skipped. If there is at least one reason why the
record can not be published, it will not be published.

Adding a reason will show the reason everywhere, where the user could publish the record. Other than dependencies,
reasons can not be ignored by the user.

### Example

```php
use In2code\In2publishCore\Component\Core\Reason\Reason;
use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;

class RecordShouldBeSkippedVoter
{
    public const RECORD_UNPUBLISHABLE_BECAUSE___ = 1;

    public function __invoke(CollectReasonsWhyTheRecordIsNotPublishable $event): void
    {
        $record = $event->getRecord();
        if ($this->yourDecisionLogic($record)) {
            $event->addReason(
                new Reason(
                    $this,
                    self::RECORD_UNPUBLISHABLE_BECAUSE___,
                    'LLL:EXT:your_extension/Resources/Private/Language/locallang.xlf:reason_record_unpublishable_because_'
                )
            );
        }
    }
}
```
