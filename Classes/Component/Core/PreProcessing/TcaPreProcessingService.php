<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing;

use In2code\In2publishCore\Component\Core\Service\Config\ExcludedTablesServiceInjection;
use TYPO3\CMS\Core\SingletonInterface;

use function array_flip;
use function array_intersect_key;
use function is_array;
use function ksort;

class TcaPreProcessingService implements SingletonInterface
{
    use ExcludedTablesServiceInjection;

    protected bool $initialized = false;
    /**
     * @var array<TcaPreProcessor>
     */
    protected array $processors = [];
    /**
     * Stores the part of the TCA that can be used for relation resolving
     *
     * @var array<array|null>
     */
    protected array $compatibleTca = [];
    /**
     * Stores the part of the TCA that can not be used for relation resolving including reasons
     *
     * @var array[]
     */
    protected array $incompatibleTca = [];

    public function register(TcaPreProcessor $processor): void
    {
        $this->processors[$processor->getType()][$processor->getTable()][$processor->getColumn()] = $processor;
    }

    protected function getProcessor(string $type, string $table, string $column): ?TcaPreProcessor
    {
        return $this->processors[$type][$table][$column]
            ?? $this->processors[$type][$table]['*']
            ?? $this->processors[$type]['*']['*']
            ?? null;
    }

    public function getIncompatibleTcaParts(): array
    {
        $this->initialize();
        return $this->incompatibleTca;
    }

    public function getCompatibleTcaParts(): array
    {
        $this->initialize();

        return $this->compatibleTca;
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        $tables = $this->excludedTablesService->getAllNonExcludedTcaTables();
        $tca = array_intersect_key($GLOBALS['TCA'], array_flip($tables));
        ksort($tca);
        $tca = $this->enableRichtextForColumnsUsedInInlineWithRichtextOverride($tca);

        foreach ($tca as $table => $tableConfig) {
            if (isset($tableConfig['columns']) && is_array($tableConfig['columns'])) {
                $this->preProcessTcaColumns($table, $tableConfig['columns']);
            }
        }
    }

    /**
     * If any TCA type adds enableRichtext to a column, we must process the column by setting a resolver.
     * We add enableRichtext to the TCA column if at least one type adds it via override.
     * If we didn't do that we wouldn't have a resolver for e.g. tt_content bodytext.
     */
    public function enableRichtextForColumnsUsedInInlineWithRichtextOverride(array $tca): array
    {
        foreach ($tca as $table => $tableConfig) {
            if (isset($tableConfig['types']) && is_array($tableConfig['types'])) {
                foreach ($tableConfig['types'] as $typeConfig) {
                    if (isset($typeConfig['columnsOverrides']) && is_array($typeConfig['columnsOverrides'])) {
                        foreach ($typeConfig['columnsOverrides'] as $column => $overrideConfig) {
                            if ($overrideConfig['config']['enableRichtext'] ?? false) {
                                /** @noinspection UnsupportedStringOffsetOperationsInspection */
                                $tca[$table]['columns'][$column]['config']['enableRichtext'] = true;
                            }
                        }
                    }
                }
            }
        }
        return $tca;
    }

    public function preProcessTcaColumns(string $table, array $columnsConfig): void
    {
        foreach ($columnsConfig as $column => $columnConfig) {
            // if the column has no config section like sys_file_metadata[columns][height]
            if (!isset($columnConfig['config']) || !is_array($columnConfig['config'])) {
                $this->incompatibleTca[$table][$column] = 'Columns without config section can not hold relations';
                continue;
            }

            $type = $columnConfig['config']['type'];

            // If there's no processor for the type it is not a standard type of TYPO3
            // The incident will be logged and the field will be skipped
            $tcaPreProcessor = $this->getProcessor($type, $table, $column);
            if (null === $tcaPreProcessor) {
                $this->incompatibleTca[$table][$column] = 'The type "'
                    . $type
                    . '" can not hold relations or there was no pre processor defined';
                continue;
            }

            $processingResult = $tcaPreProcessor->process($table, $column, $columnConfig['config']);
            if ($processingResult->isCompatible()) {
                $this->compatibleTca[$table][$column] = $processingResult->getValue();
            } else {
                $this->incompatibleTca[$table][$column] = $processingResult->getValue();
            }
        }
    }
}
