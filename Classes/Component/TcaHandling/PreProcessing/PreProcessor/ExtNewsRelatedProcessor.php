<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

use Closure;
use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\DatabaseIdentifierQuotingService;
use In2code\In2publishCore\Domain\Model\Record;

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

    protected function buildResolver(string $table, string $column, array $processedTca): Closure
    {
        return static function (Demands $demands, Record $record): void {
            $demands->addJoin(
                'tx_news_domain_model_news_related_mm',
                'tx_news_domain_model_news',
                '',
                'uid_foreign',
                $record->getId(),
                $record
            );
        };
    }
}
