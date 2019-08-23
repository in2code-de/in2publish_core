# Deprecation: CommonRepository convertToRecord method

Issue https://projekte.in2code.de/issues/36641

## Description

The method `\In2code\In2publishCore\Domain\Repository\CommonRepository::convertToRecord`
does two things. First it will set a tableName if not provided as argument,
which is deprecated and secondly calls RecordFactory with all provided arguments.
This method is therefore only an anemic wrapper around `RecordFactory::makeInstance` which can be removed.

## Impact

Deprecated Methods:
1. Calling the method `\In2code\In2publishCore\Domain\Repository\CommonRepository::convertToRecord` is deprecated.

## Affected Installations

All classes extending the `CommonRepository` and calling `convertToRecord` directly are affected.

## Migration

```PHP
// Replace this old call
$this->convertToRecord($this, $localProperties, $foreignProperties, $tableName, $idFieldName);
// With the call to `RecordFactory::makeInstance`:
$this->recordFactory->makeInstance($this, $localProperties, $foreignProperties, [], $tableName, $idFieldName);
```
Do not instantiate the `CommonRepository` with an `identifierFieldName`.

If you extended the `BaseRepository` or `CommonRepository` you should consider using public methods provided by these classes.
If that is not possible for your use case feel free to provide a pull request with a new signal or added optional arguments.
