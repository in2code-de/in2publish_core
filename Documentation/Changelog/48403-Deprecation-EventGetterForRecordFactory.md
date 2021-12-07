# Deprecation: Event getter for RecordFactory

Issue https://projekte.in2code.de/issues/48403

## Description

An instance of the singleton class `\In2code\In2publishCore\Domain\Factory\RecordFactory` is passed to a few events,
which is not necessary (because it is a singleton). Therefore, all instances of the `RecordFactory` will be removed from
the affected events.

## Impact

* The method `getRecordFactory` of following events is deprecated and will be removed in in2publish_core v11.
    * `\In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord`
    * `\In2code\In2publishCore\Event\RecordInstanceWasInstantiated`
    * `\In2code\In2publishCore\Event\RootRecordCreationWasFinished`

## Affected Installations

All.

## Migration

Please use dependency injection or `GeneralUtility::makeInstance` to retrieve the instance of the `RecordFactory`.
