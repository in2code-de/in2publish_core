<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\RecordCollection;

class DemandBuilder
{
    use ResolverServiceInjection;

    protected DemandsFactory $demandsFactory;

    public function injectDemandsFactory(DemandsFactory $demandsFactory): void
    {
        $this->demandsFactory = $demandsFactory;
    }

    public function buildDemandForRecords(RecordCollection $records): Demands
    {
        $demand = $this->demandsFactory->createDemand();
        foreach ($records->getRecordsFlat() as $record) {
            $classification = $record->getClassification();
            $resolvers = $this->resolverService->getResolversForTable($classification);
            foreach ($resolvers as $resolver) {
                $resolver->resolve($demand, $record);
            }
        }
        return $demand;
    }
}
