# CommonRepositoryWasInstantiated

Replaces the `\In2code\In2publishCore\Reposistory\CommonRepository / instanceCreated` Signal.

## When

Each time an instances of the CommonRepository is instantiated.

## What

* `commonRepository`: The instance of the CommonRepository which is currently being instantiated.

## Possibilities

You can listen on this event to dynamically register slots with objects on signals from the CommonRepository.
This increases performance on signals which are dispatched very often.

### Example

This example shows how to dynamically register a slot with an object when the CommonRepository was instantiated.

```php
// from ext_tables.php
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
);
$signalSlotDispatcher->connect(
    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
    'instanceCreated',
    function () use ($signalSlotDispatcher) {
        $voter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \In2code\In2publishCore\Features\SkipEmptyTable\SkipTableVoter::class
        );
        /** @see \In2code\In2publishCore\Features\SkipEmptyTable\SkipTableVoter::shouldSkipFindByIdentifier() */
        $signalSlotDispatcher->connect(
            \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
            'shouldSkipFindByIdentifier',
            $voter,
            'shouldSkipFindByIdentifier'
        );
    }
);
```
