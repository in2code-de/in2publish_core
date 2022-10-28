<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RecordBreadcrumbs\DependencyInjection\RecordExtensionsProvider;

use In2code\In2publishCore\Component\Core\DependencyInjection\RecordExtensionProvider\RecordExtensionsProvider;
use In2code\In2publishCore\Features\RecordBreadcrumbs\Domain\Extensions\BreadcrumbExtension;

class CoreRecordExtensionsProvider implements RecordExtensionsProvider
{
    public function getExtensions(): array
    {
        return [
            BreadcrumbExtension::class,
        ];
    }
}
