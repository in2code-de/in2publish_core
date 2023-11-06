<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Service;

use In2code\In2publishCore\Component\Core\PreProcessing\CachedTcaPreProcessingServiceInjection;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;

class ResolverService
{
    use RelevantTablesServiceInjection;
    use CachedTcaPreProcessingServiceInjection;

    /**
     * @var array<string, array<string, Resolver>>
     */
    protected array $resolvers;

    public function initializeObject(): void
    {
        $compatibleTcaParts = $this->cachedTcaPreProcessingService->getCompatibleTcaParts();
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
