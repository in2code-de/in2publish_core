<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\RecordCollection;

class DemandBuilder
{
    use ResolverServiceInjection;
    use DemandsFactoryInjection;

    public function buildDemandForRecords(RecordCollection $records): Demands
    {
        $demand = $this->demandsFactory->createDemand();
        foreach ($records->getRecordsFlat() as $record) {
            $classification = $record->getClassification();
            $resolvers = $this->resolverService->getResolversForClassification($classification);
            foreach ($resolvers as $resolver) {
                $resolver->resolve($demand, $record);
            }
        }
        return $demand;
    }
}
