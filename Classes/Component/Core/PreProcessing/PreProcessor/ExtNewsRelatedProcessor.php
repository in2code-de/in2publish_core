<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;

class ExtNewsRelatedProcessor extends AbstractProcessor
{
    protected string $type = 'group';

    public function getTable(): string
    {
        return 'tx_news_domain_model_news';
    }

    public function getColumn(): string
    {
        return 'related';
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Resolver
    {
        $resolver = $this->container->get(StaticJoinResolver::class);
        $resolver->configure(
            'tx_news_domain_model_news_related_mm',
            'tx_news_domain_model_news',
            '',
            'uid_foreign'
        );
        return $resolver;
    }
}
