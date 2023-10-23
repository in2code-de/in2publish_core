<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Service\ReplaceMarkersServiceInject;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function preg_match;
use function substr;
use function trim;

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
        $additionalWhere = trim($additionalWhere);
        if (str_starts_with($additionalWhere, 'AND ')) {
            $additionalWhere = trim(substr($additionalWhere, 4));
        }
        if (1 === preg_match(AbstractProcessor::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
        }

        $splitValues = GeneralUtility::trimExplode(',', $value);
        foreach ($splitValues as $splitValue) {
            $demands->addSelect($this->foreignTable, $additionalWhere, 'uid', $splitValue, $record);
        }
    }
}
