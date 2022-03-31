<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing;

class TcaPreProcessorRegistry
{
    /**
     * @var array<TcaPreProcessor>
     */
    protected $processors;

    public function register(TcaPreProcessor $processor): void
    {
        $this->processors[$processor->getType()][$processor->getTable()][$processor->getColumn()] = $processor;
    }

    public function getProcessor(string $type, string $table, string $column): ?TcaPreProcessor
    {
        if (isset($this->processors[$type][$table][$column])) {
            return $this->processors[$type][$table][$column];
        }
        if (isset($this->processors[$type][$table]['*'])) {
            return $this->processors[$type][$table]['*'];
        }
        if (isset($this->processors[$type]['*']['*'])) {
            return $this->processors[$type]['*']['*'];
        }
        return null;
    }
}
