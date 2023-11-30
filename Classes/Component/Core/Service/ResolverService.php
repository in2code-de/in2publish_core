<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Service;

use In2code\In2publishCore\Component\Core\PreProcessing\CachedTcaPreProcessingServiceInjection;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\StaticResolver;

class ResolverService
{
    use RelevantTablesServiceInjection;
    use CachedTcaPreProcessingServiceInjection;

    /**
     * @var array<string, array<string, Resolver>>
     */
    protected array $resolvers = [];

    /**
     * @noinspection PhpUnused Called via DI
     * @see \In2code\In2publishCore\Component\Core\DependencyInjection\StaticResolverPass
     */
    public function addStaticResolver(StaticResolver $staticResolver): void
    {
        foreach ($staticResolver->getTargetClassification() as $classification) {
            foreach ($staticResolver->getTargetProperties() as $property) {
                $this->resolvers[$classification][$property] = $staticResolver;
            }
        }
    }

    public function initializeObject(): void
    {
        $compatibleTcaParts = $this->cachedTcaPreProcessingService->getCompatibleTcaParts();
        foreach ($compatibleTcaParts as $classification => $properties) {
            foreach ($properties as $property => $array) {
                /** @var Resolver $resolver */
                $resolver = $array['resolver'];
                $targetTables = $resolver->getTargetTables();
                $relevantTables = $this->relevantTablesService->removeExcludedAndEmptyTables($targetTables);
                if (!empty($relevantTables)) {
                    $this->resolvers[$classification][$property] = $resolver;
                }
            }
        }
    }

    /**
     * @return array<string, Resolver>
     */
    public function getResolversForClassification(string $classification): array
    {
        return $this->resolvers[$classification] ?? [];
    }
}
