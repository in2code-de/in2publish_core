<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Service\ReplaceMarkersService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function preg_match;
use function substr;
use function trim;

class SelectResolver implements Resolver
{
    protected ReplaceMarkersService $replaceMarkersService;
    protected string $column;
    protected string $foreignTable;
    protected string $foreignTableWhere;

    public function __construct(ReplaceMarkersService $replaceMarkersService)
    {
        $this->replaceMarkersService = $replaceMarkersService;
    }

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
            $this->column
        );
        $additionalWhere = trim($additionalWhere);
        if (str_starts_with($additionalWhere, 'AND ')) {
            $additionalWhere = trim(substr($additionalWhere, 4));
        }
        if (1 === preg_match(AbstractProcessor::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
        }

        $splittedValues = GeneralUtility::trimExplode(',', $value);
        foreach ($splittedValues as $splittedValue) {
            $demands->addSelect($this->foreignTable, $additionalWhere, 'uid', $splittedValue, $record);
        }
    }
}
