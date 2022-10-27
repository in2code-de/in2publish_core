<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DependencyInjection\RecordExtensionProvider;

interface RecordExtensionsProvider
{
    public function getExtensions(): array;
}
