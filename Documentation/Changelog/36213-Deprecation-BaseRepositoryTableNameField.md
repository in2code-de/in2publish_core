# Deprecation: BaseRepository tableName field

Issue https://projekte.in2code.de/issues/36213

## Description

The field `\In2code\In2publishCore\Domain\Repository\BaseRepository::$tableName`
contradicts good design, obstructs testability and makes the code hard to understand.
A refactoring to ease the usage of the repository methods, especially of the derived `CommonRepository`, is required.

## Impact

Deprecated Methods:

1. Access to the field is deprecated. (`\In2code\In2publishCore\Domain\Repository\BaseRepository::getTableName`)
1. Changing the field value is deprecated. (`\In2code\In2publishCore\Domain\Repository\BaseRepository::setTableName`)
1. Replacing the field value is deprecated. (`\In2code\In2publishCore\Domain\Repository\BaseRepository::replaceTableName`)
1. Instantiation of `CommonRepository` with a `tableName` is deprecated. (`\In2code\In2publishCore\Domain\Repository\CommonRepository::__construct`)
1. Calling `CommonRepository::getDefaultInstance` with a `tableName` is deprecated. (`\In2code\In2publishCore\Domain\Repository\CommonRepository::getDefaultInstance`)

Other changes:
1. The signal `shouldSkipFindByIdentifier` now passes the `tableName` as part of the signal arguments.

## Affected Installations

All.

## Migration

Remove any call to `replaceTableName`, `setTableName` and `getTableName`, remove the `tableName` from any `CommonRepository` constructor or factory method.
Add the `tableName` to any method that requires it optionally. You will find these method names in the deprecation log.  
