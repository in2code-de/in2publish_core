<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\DatabaseIdentifierQuotingService;
use In2code\In2publishCore\Domain\Model\Record;

use function preg_match;
use function substr;
use function trim;

class InlineSelectResolver implements Resolver
{
    protected DatabaseIdentifierQuotingService $databaseIdentifierQuotingService;
    protected string $foreignTable;
    protected string $foreignField;
    protected ?string $foreignTableField;
    protected string $additionalWhere;

    public function __construct(DatabaseIdentifierQuotingService $databaseIdentifierQuotingService)
    {
        $this->databaseIdentifierQuotingService = $databaseIdentifierQuotingService;
    }

    public function configure(
        string $foreignTable,
        string $foreignField,
        ?string $foreignTableField,
        string $additionalWhere
    ): void {
        $this->foreignTable = $foreignTable;
        $this->foreignField = $foreignField;
        $this->foreignTableField = $foreignTableField;
        $this->additionalWhere = $additionalWhere;
    }

    public function getTargetTables(): array
    {
        return [$this->foreignTable];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $additionalWhere = $this->additionalWhere;
        if (null !== $this->foreignTableField) {
            $additionalWhere .= ' AND ' . $this->foreignTableField . ' = "' . $record->getClassification() . '"';
        }
        $additionalWhere = trim($additionalWhere);
        if (str_starts_with($additionalWhere, 'AND ')) {
            $additionalWhere = trim(substr($additionalWhere, 4));
        }
        $additionalWhere = $this->databaseIdentifierQuotingService->dododo($additionalWhere);
        if (1 === preg_match(AbstractProcessor::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
        }

        $demands->addSelect($this->foreignTable, $additionalWhere, $this->foreignField, $record->getId(), $record);
    }
}
