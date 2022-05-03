# RecordsWereSelectedForPublishing

_since 10.2.3, 11.0.2_

## When

As of version 10.2.3 and 11.0.2, the event is used when publishing multiple redirects in the Publish Redirect Module at
once. The Content Publisher will mark all records from the event as being published before starting to publish each
record one after another.
All records selected for publishing will instantly be marked as being published. This prevents that during long-running
requests, a record of the selected records will not be publishable by another editor.

## What

* `records`: An array of records selected for a batch publishing action.

## Possibilities

Use this event if you implement your own batch publishing method.

### Example

```php
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Event\RecordsWereSelectedForPublishing;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PublishingStateService
{
    /** @var EventDispatcher */
    protected $eventDispatcher;

    /**
     * @param array<RecordInterface> $records
     * @return void
     */
    public function publishBatchAction(array $records): void
    {
        $this->eventDispatcher->dispatch(new RecordsWereSelectedForPublishing($records));
        // ... actually publish the records
    }
}
```
