# DetermineIfRecordIsPublishing

**internal**

_since 10.2.3, 11.0.2_

## When

This event is dispatched fairly often. It is, as the name states, used to determine if a record is currently being
published by another process. The information is held in a database table, which is one of the few things shared between
processes. This event might be removed.

After isPublishing was set to `true`, no other listeners will be invoked. The determination can not be reversed.

## What

* `publishing`: A bool indicating if the record is publishing (`true`) or not publishing (`false`)
* `tableName`: The record's table name
* `identifier`: The record's uid or compoined identifier. The type is `int` in most cases, but can be `string` if the
  record is an MM table entry.

## Possibilities

If you use a special publisher that does not lead to the record being recognized by the `RunningRequestsService`, you
have the possibility to mark each record as "currently being published" by means of your own listener.

### Example

```php
use In2code\In2publishCore\Event\DetermineIfRecordIsPublishing;

class PublishingStateService
{
    protected $inPublishing = [];

    public function __invoke(DetermineIfRecordIsPublishing $event): void
    {
        $table = $event->getTableName();
        $identifier = $event->getIdentifier();
        if (isset($this->inPublishing[$table][$identifier])) {
            $event->setIsPublishing();
        }
    }
}
```
