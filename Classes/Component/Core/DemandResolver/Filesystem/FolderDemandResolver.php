<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\FolderDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\MissingFolderInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\ForeignFolderInfoServiceInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFolderInfoServiceInjection;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Node;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndexInjection;

use function array_keys;

class FolderDemandResolver implements DemandResolver
{
    use RecordFactoryInjection;
    use RecordIndexInjection;
    use LocalFolderInfoServiceInjection;
    use ForeignFolderInfoServiceInjection;

    public function resolveDemand(Demands $demands, RecordCollection $recordCollection): void
    {
        /** @var array<int, array<string, array<string, Node>>> $folderDemands */
        $folderDemands = $demands->getDemandsByType(FolderDemand::class);
        if (empty($folderDemands)) {
            return;
        }

        $request = [];
        foreach ($folderDemands as $storage => $folderIdentifiers) {
            $request[$storage] = array_keys($folderIdentifiers);
        }

        $localResponseCollection = $this->localFolderInfoService->getFolderInfo($request);
        $foreignResponseCollection = $this->foreignFolderInfoService->getFolderInformation($request);

        foreach ($folderDemands as $storage => $identifiers) {
            foreach ($identifiers as $identifier => $valueMap) {
                $localInfo = $localResponseCollection->getInfo($storage, $identifier);
                $foreignInfo = $foreignResponseCollection->getInfo($storage, $identifier);

                $combinedIdentifier = $storage . ':' . $identifier;

                $folderRecord = $this->recordIndex->getRecord(FolderRecord::CLASSIFICATION, $combinedIdentifier);
                if (null === $folderRecord) {
                    if ($localInfo instanceof MissingFolderInfo && $foreignInfo instanceof MissingFolderInfo) {
                        continue;
                    }

                    $folderRecord = $this->recordFactory->createFolderRecord(
                        $localInfo->toArray(),
                        $foreignInfo->toArray(),
                    );
                    if (null === $folderRecord) {
                        continue;
                    }
                    $recordCollection->addRecord($folderRecord);
                }
                foreach ($valueMap as $record) {
                    $record->addChild($folderRecord);
                }
            }
        }
    }
}
