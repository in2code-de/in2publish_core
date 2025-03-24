<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\Resolver\GroupSingleTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;

class FolderProcessor extends AbstractProcessor
{
    protected string $type = 'folder';

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        if ($this->excludedTablesService->isExcludedTable('sys_file_reference')) {
            return ['The table sys_file_reference is excluded from publishing'];
        }
        return [];
    }

    protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver
    {
        $foreignTable = 'sys_file_reference';

        $resolver = $this->container->get(GroupSingleTableResolver::class);
        $resolver->configure(
            $foreignTable,
            $column
        );
        return $resolver;
    }
}
