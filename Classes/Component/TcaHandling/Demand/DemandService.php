<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Demand;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;

use function array_column;

class DemandService
{
    protected array $preProcessedTca;

    public function injectTcaPreProcessingService(TcaPreProcessingService $tcaPreProcessingService): void
    {
        $this->preProcessedTca = $tcaPreProcessingService->getCompatibleTcaParts();
    }

    public function buildDemandForRecords(RecordCollection $records): Demands
    {
        $demand = new Demands();
        foreach ($records->getRecordsFlat() as $record) {
            $preProcessedTca = $this->preProcessedTca[$record->getClassification()] ?? [];
            /** @var Resolver $resolver */
            $resolvers = array_column($preProcessedTca, 'resolver');
            foreach ($resolvers as $resolver) {
                $resolver->resolve($demand, $record);
            }
        }
        return $demand;
    }

}
