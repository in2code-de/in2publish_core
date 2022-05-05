<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\DatabaseIdentifierQuotingService;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Service\ReplaceMarkersService;

use function preg_match;

class SelectMmResolver implements Resolver
{
    protected DatabaseIdentifierQuotingService $databaseIdentifierQuotingService;
    protected ReplaceMarkersService $replaceMarkersService;
    protected string $foreignTableWhere;
    protected string $column;
    protected string $mmTable;
    protected string $foreignTable;
    protected string $selectField;

    public function __construct(
        DatabaseIdentifierQuotingService $databaseIdentifierQuotingService,
        ReplaceMarkersService $replaceMarkersService
    ) {
        $this->databaseIdentifierQuotingService = $databaseIdentifierQuotingService;
        $this->replaceMarkersService = $replaceMarkersService;
    }

    public function configure(
        string $foreignTableWhere,
        string $column,
        string $mmTable,
        string $foreignTable,
        string $selectField
    ): void {
        $this->foreignTableWhere = $foreignTableWhere;
        $this->column = $column;
        $this->mmTable = $mmTable;
        $this->foreignTable = $foreignTable;
        $this->selectField = $selectField;
    }

    public function getTargetTables(): array
    {
        return [$this->foreignTable];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $additionalWhere = $this->replaceMarkersService->replaceMarkers(
            $record,
            $this->foreignTableWhere,
            $this->column
        );
        $additionalWhere = $this->databaseIdentifierQuotingService->dododo($additionalWhere);
        if (1 === preg_match(AbstractProcessor::ADDITIONAL_ORDER_BY_PATTERN, $additionalWhere, $matches)) {
            $additionalWhere = $matches['where'];
        }

        $value = $record->getId();

        $demands->addJoin($this->mmTable, $this->foreignTable, $additionalWhere, $this->selectField, $value, $record);
    }
}
