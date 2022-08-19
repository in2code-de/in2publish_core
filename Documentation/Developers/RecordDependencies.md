# Record Dependencies

New in v12 of the Content Publisher, Dependencies represent non-TCA connections to other records, which have to be
published so that the depending record can be published.

## Properties

* `record`: The record which has the dependency
* `classification`: The classification of the record that is required
* `properties`: An array of `['property' => 'value']` pairs that identify the required record, usually `['uid' => 123]`.
* `requirement`: One of the `Dependency::REQ_*` constants.
* `label`: A full label identifier (starting with `LLL:`) which is rendered when the required record does not meet the
  requirements.
* `labelArgumentsFactory`: A `Closure` with signature `function (Record $record): array;` which returns an array of
  values for interpolation with the label.

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
