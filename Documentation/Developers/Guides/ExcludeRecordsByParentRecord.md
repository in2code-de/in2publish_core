# Exclude records by parent record property and state

This is a rather tricky requirement, because since v12, the content publisher works in a multistep approach which
separates TCA and data processing. When processing the TCA, the content publisher does not know about the data, yet.
When processing the data, the content publisher delegates everything to the right object and therefore does not know
about the TCA anymore.

The solution is to introduce your logic in both parts. You have to create a `PreProcessor` which knows about the table
and the column you want to exclude conditionally.

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExt\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerServiceInjection;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use MyVendor\MyExt\Component\Core\Resolver\SkipEmployeesWhenCompanyIsDeletedResolver;

/**
 * This processor only cares about the company's employees property by limiting it with getTable/getColumn.
 * It is assumed, that the TCA is a type=select relation without an MM table.
 * We can hardcode the resolver because we know the TCA of tx_myext_domain_model_company.employees
 */
class CompanyEmployeesProcessor extends AbstractProcessor
{
    use TcaEscapingMarkerServiceInjection;

    public function getTable(): string
    {
        return 'tx_myext_domain_model_company';
    }

    public function getColumn(): string
    {
        return 'employees';
    }

    protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver
    {
        $foreignTableWhere = $this->tcaEscapingMarkerService->escapeMarkedIdentifier(
            $processedTca['foreign_table_where'] ?? ''
        );

        /** @var SkipEmployeesWhenCompanyIsDeletedResolver $resolver */
        $resolver = $this->container->get(SkipEmployeesWhenCompanyIsDeletedResolver::class);
        $resolver->configure($column, $processedTca['foreign_table'], $foreignTableWhere);
        return $resolver;
    }
}
```

The actual condition based on the **data** is done in the `Resolver`:

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExt\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\Resolver\SelectResolver;

class SkipEmployeesWhenCompanyIsDeletedResolver extends SelectResolver
{
    public function resolve(Demands $demands, Record $record): void
    {
        if ($record->getState() === Record::S_DELETED || $record->getState() === Record::S_SOFT_DELETED) {
            return;
        }
        parent::resolve($demands, $record);
    }
}
```

Hints:

* You have to clear your Dependency Injection caches after creating the `PreProcessor` (Flush Caches in the Maintenance
  Module or `rm -rf var/cache/code/di`. Your processor is then automatically registered via DI.
* No other resolver will try to resolve the relations of the column tx_myext_domain_model_company.employees
* You can use wildcards for the column name or both table and column names.
