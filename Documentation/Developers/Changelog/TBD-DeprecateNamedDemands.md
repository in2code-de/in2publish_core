# Deprecation: Anemic TcaService methods

Issue TBD

## Description

The following methods of the interface and all implementations are deprecated.

* `\In2code\In2publishCore\Component\Core\Demand\Demands::addSelect`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::unsetSelect`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::addJoin`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::unsetJoin`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::addFile`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::getFiles`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::addSysRedirectSelect`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::getSysRedirectSelect`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::uniqueRecordKey`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::getSelect`
* `\In2code\In2publishCore\Component\Core\Demand\Demands::getJoin`

## Impact

The mentioned methods will be removed in in2publish_core v13.

## Affected Installations

All.

## Migration

The migration is very easy. Instead of the old `addXYZ` method, you call `addDemand` with the matching demand type.
Instead of the old `unsetXYZ` method, you call `unsetDemand` with the matching demand remover type.

```php
use In2code\In2publishCore\Component\Core\Demand\Type\FileDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\JoinDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SysRedirectDemand;
use In2code\In2publishCore\Component\Core\Demand\Remover\JoinDemandRemover;
use In2code\In2publishCore\Component\Core\Demand\Remover\SelectDemandRemover;

/**
 * @var \In2code\In2publishCore\Component\Core\Demand\Demands $demands
 * @var \In2code\In2publishCore\Component\Core\Record\Model\Record $record
 */

// addSelect -> addDemand(new SelectDemand(...))
// OLD
$demands->addSelect('target_table', '1=1', 'uid', 1, $record);
// NEW
$demands->addDemand(new SelectDemand('target_table', '1=1', 'uid', 1, $record));

// unsetSelect -> unsetDemand(new SelectDemandRemover(...))
// OLD
$demands->unsetSelect('target_table', 'uid', 1);
// NEW
$demands->unsetDemand(new SelectDemandRemover('target_table', 'uid', 1));

// addJoin -> addDemand(new JoinDemand(...))
// OLD
$demands->addJoin('target_table_mm', 'target_table', '1=1', 'uid', 1, $record);
// NEW
$demands->addDemand(new JoinDemand('target_table_mm', 'target_table', '1=1', 'uid', 1, $record));

// unsetJoin -> unsetDemand(new JoinDemandRemover(...))
// OLD
$demands->unsetJoin('target_table', 'uid', 1);
// NEW
$demands->unsetDemand(new JoinDemandRemover('target_table', 'uid', 1));

// addFile -> addDemand(new FileDemand(...))
// OLD
$demands->addFile(1, '/foo/bar', $record);
// NEW
$demands->addDemand(new FileDemand(1, '/foo/bar', $record));

// addSysRedirectSelect -> addDemand(new SysRedirectDemand(...))
// OLD
$demands->addSysRedirectSelect('target_table', '1=1', $record);
// NEW
$demands->addDemand(new SysRedirectDemand('target_table', '1=1', $record));
```
