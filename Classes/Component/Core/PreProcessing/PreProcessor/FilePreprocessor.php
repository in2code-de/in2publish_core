<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\Resolver\InlineSelectResolver;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;

class FilePreprocessor extends AbstractProcessor
{
    protected string $type = 'file';

    protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver
    {
        $foreignTable = 'sys_file_reference';
        $foreignField = 'uid_foreign';
        $foreignTableField = 'tablenames';

        $resolver = $this->container->get(InlineSelectResolver::class);
        $resolver->configure(
            $foreignTable,
            $foreignField,
            $foreignTableField,
            '',
        );
        return $resolver;
    }
}
