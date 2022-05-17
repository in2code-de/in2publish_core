# Deprecation: TcaProcessingService static access

Issue https://projekte.in2code.de/issues/48403

## Description

The class `\In2code\In2publishCore\Domain\Service\TcaProcessingService` has been accessed statically since it was first
created, despite the fact that it _requires_ an internal state and should have been a singleton all along. This change
deprecates all static methods of the class and introduces non-static methods for those methods, which still make sense.

## Impact

Following methods are deprecated:

* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::getIncompatibleTca`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::getCompatibleTca`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::getControls`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::getAllTables`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::tableExists`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::getCompleteTca`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::getCompleteTcaForTable`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::getColumnsFor`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::getControlsFor`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::hasDeleteField`
* `\In2code\In2publishCore\Domain\Service\TcaProcessingService::getDeleteField`

## Affected Installations

Only if you are using the public API of the class `TcaProcessingService`.

## Migration

Some methods have a non-static counterpart. These are:

* `::getIncompatibleTca` => `->getIncompatibleTcaParts`
* `::getCompatibleTca` => `->getCompatibleTcaParts`
* `::getColumnsFor` => `->getCompatibleTcaColumns`

All other methods can be replaced by directly reading from `$GLOBALS['TCA']`.
