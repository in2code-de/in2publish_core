<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\ProcessingResult;
use In2code\In2publishCore\Component\TcaHandling\Resolver\NoOpResolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;

class ExtNewsRelatedFromProcessor extends AbstractProcessor
{
    protected NoOpResolver $noOpResolver;
    protected string $type = 'group';

    public function injectNoOpResolver(NoOpResolver $noOpResolver): void
    {
        $this->noOpResolver = $noOpResolver;
    }

    public function getTable(): string
    {
        return 'tx_news_domain_model_news';
    }

    public function getColumn(): string
    {
        return 'related_from';
    }

    public function process(string $table, string $column, array $tca): ProcessingResult
    {
        return new ProcessingResult(
            ProcessingResult::INCOMPATIBLE,
            'The field related_from is configured as owning side, but is actually the foreign side.'
        );
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Resolver
    {
        return clone $this->noOpResolver;
    }
}
