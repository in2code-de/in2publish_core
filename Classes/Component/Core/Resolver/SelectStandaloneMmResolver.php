<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\JoinDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\MmDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\StandaloneMmDemand;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Service\ReplaceMarkersService;
use In2code\In2publishCore\Service\ReplaceMarkersServiceInject;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function preg_match;

class SelectStandaloneMmResolver extends AbstractResolver
{
    use ReplaceMarkersServiceInject;

    protected string $mmTable;
    protected string $selectField;

    public function configure(
        string $mmTable,
        string $selectField
    ): void {
        $this->mmTable = $mmTable;
        $this->selectField = $selectField;
    }

    public function getTargetTables(): array
    {
        return [$this->mmTable];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $value = $record->getId();

        $demand = new MmDemand($this->mmTable, $this->selectField, $value, $record);

        $demands->addDemand($demand);
    }

    public function __serialize(): array
    {
        return [
            'metaInfo' => $this->metaInfo,
            'mmTable' => $this->mmTable,
            'selectField' => $this->selectField,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->metaInfo = $data['metaInfo'];
        $this->configure(
            $data['mmTable'],
            $data['selectField'],
        );
        $this->injectReplaceMarkersService(GeneralUtility::makeInstance(ReplaceMarkersService::class));
    }
}
