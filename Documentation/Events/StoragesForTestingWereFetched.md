# StoragesForTestingWereFetched

Replaces the `\In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider / filterStorages` Signal.

## When

This event is triggered when the `\In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider` was called to
fetch the FAL storages from the database.

## What

* `localStorages`: An array that contains all storages from local.
* `foreignStorages`: An array that contains all storages from foreign.
* `purpose`: A string which tells you for what kind of test these storages were fetched from the database.

## Possibilities

You can modify the `localStorages` and `foreignStorages` in the event to overwrite the storages used in the tests.
Removing or adding storages in the event listener does not have an impact on the publishing or record tree building
process.

### Example

This example shows you how to exclude all storages from the Publish Tools Module tests which are called "Private".

```php
use In2code\In2publishCore\Event\StoragesForTestingWereFetched;

class StoragesModifierEventListener
{
    public function onStoragesForTestingWereFetched(StoragesForTestingWereFetched $event): void
    {
        $localStorages = $event->getLocalStorages();
        foreach ($localStorages as $index => $storage) {
            if ('Private' === $storage['name']) {
                unset($localStorages[$index]);
            }
        }
        $event->setLocalStorages($localStorages);
    }
}
```
