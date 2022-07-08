<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Service;

use In2code\In2publishCore\Component\Core\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;

class ResolverService
{
    protected RelevantTablesService $relevantTablesService;
    protected TcaPreProcessingService $tcaPreProcessingService;
    /**
     * @var array<string, array<string, Resolver>>
     */
    protected array $resolvers;

    public function injectRelevantTablesService(RelevantTablesService $relevantTablesService): void
    {
        $this->relevantTablesService = $relevantTablesService;
    }

    public function injectTcaPreProcessingService(TcaPreProcessingService $tcaPreProcessingService): void
    {
        $this->tcaPreProcessingService = $tcaPreProcessingService;
    }

    public function initializeObject(): void
    {
        $compatibleTcaParts = $this->tcaPreProcessingService->getCompatibleTcaParts();
        foreach ($compatibleTcaParts as $table => $properties) {
            foreach ($properties as $property => $array) {
                /** @var Resolver $resolver */
                $resolver = $array['resolver'];
                $targetTables = $resolver->getTargetTables();
                $relevantTables = $this->relevantTablesService->removeExcludedAndEmptyTables($targetTables);
                if (!empty($relevantTables)) {
                    $this->resolvers[$table][$property] = $resolver;
                }
            }
        }
    }

    /**
     * @return array<string, Resolver>
     */
    public function getResolversForTable(string $table): array
    {
        return $this->resolvers[$table] ?? [];
    }
}
