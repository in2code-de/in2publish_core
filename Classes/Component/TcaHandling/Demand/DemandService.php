<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Demand;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;

use function array_replace_recursive;

class DemandService
{
    protected array $preProcessedTca;

    public function injectTcaPreProcessingService(TcaPreProcessingService $tcaPreProcessingService): void
    {
        $this->preProcessedTca = $tcaPreProcessingService->getCompatibleTcaParts();
    }

    public function buildDemandForRecords(RecordCollection $records): array
    {
        $demand = [];
        foreach ($records->getRecordsFlat() as $record) {
            $demand[] = $this->buildDemand($record);
        }
        return array_replace_recursive([], ...$demand);
    }

    protected function buildDemand(DatabaseRecord $record): array
    {
        $demand = [];
        $preProcessedTca = $this->preProcessedTca[$record->getClassification()] ?? [];
        foreach ($preProcessedTca as $column) {
            $demand[] = $column['resolver']($record);
        }
        return array_replace_recursive([], ...$demand);
    }
}
