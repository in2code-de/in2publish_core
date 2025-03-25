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
        return [
            'The type "folder" is not supported by the system',
        ];
    }

    protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver
    {
        return null;
    }
}
