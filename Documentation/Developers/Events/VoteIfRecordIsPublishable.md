# VoteIfRecordIsPublishable

Replaces the `\In2code\In2publishCore\Domain\Model\RecordInterface / isPublishable` Signal.

**This voting event is different from the normal voting rules.**

* The sum of all votes will be the result.
* The voting result will be `true` when there are more "Yes" than "No" votes, **or if the voting is a draw**.
* If more event listeners voted for "No" the voting result will be `false`.

## When

This event is fired each time when a record is asked if it is publishable or not. This occurs during the rendering of
records in the Publish Overview Module and during publishing. Additionally, it can occur if the context menu publishing
feature is active.

## What

* `table`: The record's table name.
* `identifier`: The record's identifier, a.k.a UID.

## Possibilities

With this event you can implement your own rules if records are publishable or not. The Content Publisher Enterprise
Edition makes exzessive use of this event to implement the rules of the workflow feature.

### Example

This example shows you how to implement a rule which will mark a `tt_content` record as non-publishable if it has a
custom flag set.

```php
use In2code\In2publishCore\Event\VoteIfRecordIsPublishable;
use In2code\In2publishCore\Service\Database\RawRecordService;

class RecordIsPublishableVoter
{
    protected RawRecordService $rawRecordService;

    public function __construct(RawRecordService $rawRecordService)
    {
        $this->rawRecordService = $rawRecordService;
    }

    public function __invoke(VoteIfRecordIsPublishable $event): void
    {
        // Do not forget to check if the current record is from the table you want.
        if ('tt_content' === $event->getTable()) {
            // Use the RawRecordService to reduce queries if possible and have a sleek API
            $rawRecord = $this->rawRecordService->getRawRecord('tt_content', $event->getIdentifier(), 'local');
            if (true === (bool)($rawRecord['tx_myext_dontpublish'] ?? false)) {
                $event->voteYes();
            }
        }
    }
}
```
