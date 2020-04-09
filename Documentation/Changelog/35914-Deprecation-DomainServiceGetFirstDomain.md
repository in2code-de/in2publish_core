# Deprecation: BaseRepository tableName field

Issue https://projekte.in2code.de/issues/35914

## Description

The method `\In2code\In2publishCore\Domain\Service\DomainService::getFirstDomain` is not used anymore.
The only call was from the `\In2code\In2publishCore\ViewHelpers\File\BuildResourcePathViewHelper` which was refactored
to use the filePreviewDomain directly.

## Impact

Deprecated Methods:
1. `\In2code\In2publishCore\Domain\Service\DomainService::getFirstDomain`

## Affected Installations

All.

## Migration

If your record is from sys_file you have to directly access the configuration value `filePreviewDomainName`.
Otherwise you can use `\In2code\In2publishCore\Domain\Service\DomainService::getDomainFromSiteConfigByPageId`.
