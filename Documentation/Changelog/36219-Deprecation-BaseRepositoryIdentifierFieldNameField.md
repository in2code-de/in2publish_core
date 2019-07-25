# Deprecation: BaseRepository identifierFieldName field

Issue https://projekte.in2code.de/issues/36219

## Description

The field `\In2code\In2publishCore\Domain\Repository\BaseRepository::$identifierFieldName`
contradicts good design, obstructs testability and makes the code hard to understand.
A refactoring to ease the usage of the repository methods, especially of the derived `CommonRepository`, is required.

## Impact

Deprecated Methods:
1. Access to the field is deprecated. (`\In2code\In2publishCore\Domain\Repository\BaseRepository::getIdentifierFieldName`)
1. Instantiation of `CommonRepository` with an `identifierFieldName` is deprecated. (`\In2code\In2publishCore\Domain\Repository\CommonRepository::__construct`)
1. `findByIdentifierInOtherTable` is deprecated as it does the exact same as `findByIdentifier`

## Affected Installations

All.

## Migration

Remove any call to `getIdentifierFieldName`.
Do not instantiate the `CommonRepository` with an `identifierFieldName`.

If you extended the `BaseRepository` or `CommonRepository` you should consider using public methods provided by these classes.
If that is not possible for your use case feel free to provide a pull request with a new signal or added optional arguments.
