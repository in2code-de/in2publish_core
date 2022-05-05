<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Demand;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Component\TcaHandling\Service\ResolverService;

class DemandService
{
    protected ResolverService $resolverService;

    public function injectResolverService(ResolverService $resolverService): void
    {
        $this->resolverService = $resolverService;
    }

    public function buildDemandForRecords(RecordCollection $records): Demands
    {
        $demand = new Demands();
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
