# Record Dependencies

New in v12 of the Content Publisher, Dependencies represent non-TCA connections to other records, which have to be
published so that the depending record can be published.

## Properties

* `record`: The record which has the dependency
* `classification`: The classification of the record that is required
* `properties`: An array of `['property' => 'value']` pairs that identify the required record, usually `['uid' => 123]`.
* `requirement`: One of the `Dependency::REQ_*` constants, see [Requirements](#requirements).
* `label`: A full label identifier (starting with `LLL:`) which is rendered when the required record does not meet the
  requirements.
* `labelArgumentsFactory`: A `Closure` with signature `function (Record $record): array;` which returns an array of
  values for interpolation with the label.

## Requirements

The requirement defines which condition the required record must meet.

| Constant | The required record must ... |
|---|---|
| `REQ_EXISTING` | exist on foreign, i.e. its state must not be "added". |
| `REQ_CONSISTENT_EXISTENCE` | exist on both sides or on neither side. Used for translation parents to prevent local and foreign from disagreeing about the existence of the relation target. |
| `REQ_ENABLECOLUMNS` | have all inherited enable columns (hidden, starttime, ...) published. |
| `REQ_FULL_PUBLISHED` | have no changes between local and foreign, i.e. its state must be "unchanged". |
| `REQ_FULL_PUBLISHED_OR_LOCALLY_DELETED` | like `REQ_FULL_PUBLISHED`, but a target which is deleted in the local database is accepted, no matter whether that deletion has been published already. |

Use `REQ_FULL_PUBLISHED_OR_LOCALLY_DELETED` for soft references which the frontend renders tolerantly. TYPO3 keeps
such references when the target is deleted, e.g. the `records` field of a shortcut content element. Because an editor
can neither see nor restore a target which is gone on local, demanding its publication would block the depending
record permanently. `REQ_FULL_PUBLISHED` is the right choice whenever a missing target would break the depending
record.

A dependency to a record which exists in neither the local nor the foreign database never blocks publishing,
regardless of the requirement: there is nothing that could be published for such a target.

## Example

For this example we imagine two tables, `tx_myext_plugin_config` and `tx_myext_slider`.

`tx_myext_plugin_config` contains configuration for the extension's plugin. The configuration contains a UID of a page,
which will tell the plugin to render all slider records that are stored on that page. `tx_myext_slider` contains the
slider records.

To add a custom dependency, you have to create a [record subtype](DatabaseRecordSubType.md) for your table. Next,
override the method `calculateDependencies` in your record.

```php
<?php

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Dependency;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

class PluginConfigDatabaseRecord extends DatabaseRecord
{
    public function calculateDependencies() : array
    {
        $dependencies = parent::calculateDependencies();
        // Always check both local and foreign.
        $localValue = $this->localProps['pageId'] ?? null;
        $foreignValue = $this->foreignProps['pageId'] ?? null;
        if (null !== $localValue) {
            $dependencies[] = new Dependency(
                $this,
                'tx_myext_slider',
                ['pid' => $localValue],
                \In2code\In2publishCore\Component\Core\Record\Model\Dependency::REQ_FULL_PUBLISHED,
                'LLL:EXT:myext/Resources/Private/Language/locallang.xml:record.dependency.slider.published',
                static fn (Record $record): array => [$record->__toString()]
            );
        }
        // If the foreign value is different from the local value, create another dependency with the foreign value.
        if (null !== $foreignValue && $localValue !== $foreignValue) {
            $dependencies[] = new Dependency(
                $this,
                'tx_myext_slider',
                ['pid' => $foreignValue],
                \In2code\In2publishCore\Component\Core\Record\Model\Dependency::REQ_FULL_PUBLISHED,
                'LLL:EXT:myext/Resources/Private/Language/locallang.xml:record.dependency.slider.published',
                static fn (Record $record): array => [$record->__toString()]
            );
        }
        return $dependencies;
    }
}
```

When checking if the requirements are met, the Content Publisher will search for all records from `tx_myext_slider`
with `pid IN ($localValue, $foreignValue)` and check if there are no changes between local and foreign (`ignoredProps`
apply). For any record not matching the criteria, the label will be rendered using the `labelArgumentsFactory` to
convert each record into a readable string that is used in the label to tell the editor which record exactly must be
published to fulfill the dependencies.
