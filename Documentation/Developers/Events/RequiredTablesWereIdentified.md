# RequiredTablesWereIdentified

Replaces the `\In2code\In2publishCore\Testing\Data\RequiredTablesDataProvider / overruleTables` Signal.

## When

* Before the `RequiredTablesDataProvider` returns the list of tables which are required for in2publish_core to work.

## What

* `tables`: The list of tables which are required.

## Possibilities

You can alter the list of required tables to include your own. You could remove a table from the list, but you should
not do that. You should only add tables which are required for the Content Publisher to work.

### Example

A short example which adds a table:

```php
use In2code\In2publishCore\Event\RequiredTablesWereIdentified;

class UserPublishingDecider
{
    public function __invoke(RequiredTablesWereIdentified $event): void
    {
        $event->addTable('tx_mytable_publishing_table');
    }
}
```
