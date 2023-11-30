<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\TextResolver;

class LinkProcessor extends AbstractProcessor
{
    protected string $type = 'link';

    protected function buildResolver(string $table, string $column, array $processedTca): Resolver
    {
        $resolver = $this->container->get(TextResolver::class);
        $resolver->configure($column);
        return $resolver;
    }
}
