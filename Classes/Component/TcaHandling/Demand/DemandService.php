<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Demand;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;

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
            foreach ($preProcessedTca as $column) {
                $column['resolver']($demand, $record);
            }
        }
        return $demand;
    }

}
