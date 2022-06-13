<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\Exception\MissingPreProcessorTypeException;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\ProcessingResult;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessor;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function array_keys;
use function array_merge;

abstract class AbstractProcessor implements TcaPreProcessor
{
    public const ADDITIONAL_ORDER_BY_PATTERN = '/(?P<where>.*)ORDER[\s\n]+BY[\s\n]+(?P<col>\w+(\.\w+)?)(?P<dir>\s(DESC|ASC))?/is';
    protected TcaPreProcessingService $tcaPreProcessingService;
    protected ContainerInterface $container;

    /**
     * Injected when PreProcessor are registered
     */
    public function setTcaPreProcessingService(TcaPreProcessingService $tcaPreProcessingService): void
    {
        $this->tcaPreProcessingService = $tcaPreProcessingService;
    }

    public function injectContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Overwrite this in your processor!
     * Return the TCA type your processor handles.
     * @api
     */
    protected string $type;
    /**
     * Overwrite this in your processor!
     * Fields that are forbidden for the processor type, indexed by the reason
     *    e.g. "itemsProcFunc is not supported by in2publish" => "itemsProcFunc"
     * @api
     */
    protected array $forbidden = [];
    /**
     * Overwrite this in your Processor!
     * Field names that must be contained in the configuration, indexed by their reason why
     *    e.g. "can not select without a foreign table" => "foreign_table"
     * @api
     */
    protected array $required = [];
    /**
     * Overwrite this in your processor if these are needed!
     * Fields that are optional and are which taken into account for relations solving. Has no specific index
     *    e.g. "foreign_table_where"
     * @api
     */
    protected array $allowed = [];

    public function getType(): string
    {
        if (!isset($this->type)) {
            throw new MissingPreProcessorTypeException($this);
        }
        return $this->type;
    }

    public function getTable(): string
    {
        return '*';
    }

    public function getColumn(): string
    {
        return '*';
    }

    public function process(string $table, string $column, array $tca): ProcessingResult
    {
        $reasons = [];
        foreach ($this->forbidden as $key => $reason) {
            if (array_key_exists($key, $tca)) {
                $reasons[] = $reason;
            }
        }
        foreach ($this->required as $key => $reason) {
            if (!array_key_exists($key, $tca)) {
                $reasons[] = $reason;
            }
        }
        foreach ($this->additionalPreProcess($table, $column, $tca) as $reason) {
            $reasons[] = $reason;
        }
        if (!empty($reasons)) {
            return new ProcessingResult(ProcessingResult::INCOMPATIBLE, $reasons);
        }
        $processedTca = [];
        foreach ($this->getImportantFields() as $importantField) {
            if (array_key_exists($importantField, $tca)) {
                $processedTca[$importantField] = $tca[$importantField];
            }
        }
        $resolver = $this->buildResolver($table, $column, $processedTca);
        if (null === $resolver) {
            return new ProcessingResult(
                ProcessingResult::INCOMPATIBLE,
                ['The processor did not return a valid resolver. The target table might be excluded or empty.']
            );
        }
        return new ProcessingResult(
            ProcessingResult::COMPATIBLE,
            ['tca' => $processedTca, 'resolver' => $resolver]
        );
    }

    protected function getImportantFields(): array
    {
        return array_merge(['type'], array_keys($this->required), $this->allowed);
    }

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        return [];
    }

    abstract protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver;
}
