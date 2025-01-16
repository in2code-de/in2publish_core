<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Service\ReplaceMarkersService;
use In2code\In2publishCore\Service\ReplaceMarkersServiceInject;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function preg_match;

class SelectResolver extends AbstractResolver
{
    use ReplaceMarkersServiceInject;

    protected string $column;
    protected string $foreignTable;
    protected string $foreignTableWhere;

    public function configure(string $column, string $foreignTable, string $foreignTableWhere): void
    {
        $this->column = $column;
        $this->foreignTable = $foreignTable;
        $this->foreignTableWhere = $foreignTableWhere;
    }

    public function getTargetTables(): array
    {
        return [$this->foreignTable];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $value = $record->getProp($this->column);
        if (empty($value)) {
            return;
        }

        $additionalWhere = $this->replaceMarkersService->replaceMarkers(
            $record,
            $this->foreignTableWhere,
            $this->column,
        );
        $additionalWhere = DatabaseUtility::stripLogicalOperatorPrefix($additionalWhere);
        if (1 === preg_match(AbstractProcessor::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
        }

        if (is_string($value)) {
            $splitValues = GeneralUtility::trimExplode(',', $value);
            foreach ($splitValues as $splitValue) {
                $demands->addDemand(new SelectDemand($this->foreignTable, $additionalWhere, 'uid', $splitValue, $record));
            }
        }
    }

    public function __serialize(): array
    {
        return [
            'metaInfo' => $this->metaInfo,
            'column' => $this->column,
            'foreignTable' => $this->foreignTable,
            'foreignTableWhere' => $this->foreignTableWhere,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->metaInfo = $data['metaInfo'];
        $this->configure($data['column'], $data['foreignTable'], $data['foreignTableWhere']);
        $this->injectReplaceMarkersService(GeneralUtility::makeInstance(ReplaceMarkersService::class));
    }
}
