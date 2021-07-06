# RecordInstanceWasInstantiated

Replaces the `\In2code\In2publishCore\Domain\Factory\RecordFactory / instanceCreated` Signal.

## When

1. Each time a new instance of `\In2code\In2publishCore\Domain\Model\Record` which represents an existing database row
   has been created, and the RecordFactory set the record state.

## What

* `recordFactory`: The instance of the record factory that created the instance of the record.
* `record`: The record instance which was created.

## Possibilities

You can do what you like with this record like changing properties, adding additional properties or adding related
records, except replacing it with a different instance.
