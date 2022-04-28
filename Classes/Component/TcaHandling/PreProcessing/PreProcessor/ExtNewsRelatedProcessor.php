<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\DatabaseIdentifierQuotingService;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\StaticJoinResolver;

class ExtNewsRelatedProcessor extends AbstractProcessor
{
    protected string $type = 'group';

    protected DatabaseIdentifierQuotingService $databaseIdentifierQuotingService;

    public function injectDatabaseIdentifierQuotingService(
        DatabaseIdentifierQuotingService $databaseIdentifierQuotingService
    ): void {
        $this->databaseIdentifierQuotingService = $databaseIdentifierQuotingService;
    }

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
        return new StaticJoinResolver(
            'tx_news_domain_model_news_related_mm',
            'tx_news_domain_model_news',
            '',
            'uid_foreign'
        );
    }
}
