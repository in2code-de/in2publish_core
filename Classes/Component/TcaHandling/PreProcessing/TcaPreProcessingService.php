<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing;

use TYPO3\CMS\Core\SingletonInterface;

use function asort;
use function is_array;
use function ksort;

class TcaPreProcessingService implements SingletonInterface
{
    /**
     * @var TcaPreProcessorRegistry
     */
    protected $tcaPreProcessorRegistry;

    protected $initialized = false;

    public function injectTcaPreProcessorRegistry(TcaPreProcessorRegistry $tcaPreProcessorRegistry): void
    {
        $this->tcaPreProcessorRegistry = $tcaPreProcessorRegistry;
    }

    /**
     * Stores the part of the TCA that can be used for relation resolving
     *
     * @var array<array|null>
     */
    protected $compatibleTca = [];

    /**
     * Stores the part of the TCA that can not be used for relation resolving including reasons
     *
     * @var array[]
     */
    protected $incompatibleTca = [];

    public function getIncompatibleTcaParts(): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->incompatibleTca;
    }

    public function getCompatibleTcaParts(): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return $this->compatibleTca;
    }

    protected function initialize(): void
    {
        $this->initialized = true;

        /*
         * If any TCA type adds enableRichtext to a column, we must process the column by setting a resolver.
         * We add enableRichtext to the TCA column if at least one type adds it via override.
         * If we didn't do that we wouldn't have a resolver for e.g. tt_content bodytext.
         */
        $tca = $GLOBALS['TCA'];
        ksort($tca);
        foreach ($tca as $table => $tableConfig) {
            if (isset($tableConfig['types']) && is_array($tableConfig['types'])) {
                foreach ($tableConfig['types'] as $typeConfig) {
                    if (isset($typeConfig['columnsOverrides']) && is_array($typeConfig['columnsOverrides'])) {
                        foreach ($typeConfig['columnsOverrides'] as $column => $overrideConfig) {
                            if ($overrideConfig['config']['enableRichtext'] ?? false) {
                                $tca[$table]['columns'][$column]['config']['enableRichtext'] = true;
                            }
                        }
                    }
                }
            }
        }

        foreach ($tca as $table => $tableConfig) {
            if (isset($tableConfig['columns']) && is_array($tableConfig['columns'])) {
                $this->preProcessTcaColumns($table, $tableConfig['columns']);
            }
        }
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
            $tcaPreProcessor = $this->tcaPreProcessorRegistry->getProcessor($type, $table, $column);
            if (null === $tcaPreProcessor) {
                $this->incompatibleTca[$table][$column] = 'The type "'
                                                          .
                                                          $type
                                                          .
                                                          '" can not hold relations or there was no pre processor defined';
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
