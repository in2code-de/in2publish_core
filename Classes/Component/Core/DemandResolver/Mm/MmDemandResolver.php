<?php

namespace In2code\In2publishCore\Component\Core\DemandResolver\Mm;

use Doctrine\DBAL\Exception;
use In2code\In2publishCore\Component\Core\Demand\CallerAwareDemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\MmDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Exception\InvalidDemandException;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndexInjection;
use In2code\In2publishCore\Component\Core\Repository\DualDatabaseRepositoryInjection;
use In2code\In2publishCore\Component\Core\Repository\ForeignSingleDatabaseRepositoryInjection;
use In2code\In2publishCore\Component\Core\Repository\LocalSingleDatabaseRepositoryInjection;

class MmDemandResolver implements DemandResolver
{
    use RecordFactoryInjection;
    use RecordIndexInjection;
    use DualDatabaseRepositoryInjection;
    use LocalSingleDatabaseRepositoryInjection;
    use ForeignSingleDatabaseRepositoryInjection;

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        foreach ($demands->getDemandsByType(MmDemand::class) as $mmTable => $fields) {
            foreach ($fields as $field => $valueMaps) {
                try {
                    $rows = $this->dualDatabaseRepository->findMmByProperty(
                        $mmTable,
                        $field,
                        array_keys($valueMaps)
                    );
                } catch (Exception $exception) {
                    if ($demands instanceof CallerAwareDemandsCollection) {
                        $callers = $demands->getMeta(MmDemand::class, $mmTable, $field);
                        $exception = new InvalidDemandException($callers, $exception);
                    }
                    throw $exception;
                }
                foreach ($rows as $mmId => $recordInfo) {
                    $mmRecord = $this->recordIndex->getRecord($mmTable, $mmId);
                    if (null === $mmRecord) {
                        $mmRecord = $this->recordFactory->createMmRecord(
                            $mmTable,
                            $mmId,
                            $recordInfo['local'] ?? [],
                            $recordInfo['foreign'] ?? [],
                        );
                        if (null === $mmRecord) {
                            continue;
                        }
                    }
                    $mapValue = $mmRecord->getProp($field);
                    foreach ($valueMaps[$mapValue] as $parent) {
                        $parent->addChild($mmRecord);
                    }
                }
            }
        }
    }
}
