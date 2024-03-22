<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\Backend\Form;

use In2code\In2publishCore\Component\Core\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

use function array_keys;
use function get_class;
use function implode;
use function sprintf;

use const PHP_EOL;

class DescriptionCompatibilityAugmentation implements FormDataProviderInterface
{
    protected array $incompatible;
    protected array $compatible;

    public function __construct(TcaPreProcessingService $tcaPreProcessingService)
    {
        $this->incompatible = $tcaPreProcessingService->getIncompatibleTcaParts();
        $this->compatible = $tcaPreProcessingService->getCompatibleTcaParts();
    }

    public function addData(array $result): array
    {
        $table = $result['tableName'];
        foreach (array_keys($result['processedTca']['columns']) as $column) {
            if (!isset($result['processedTca']['columns'][$column]['description'])) {
                // Ensure the description key exists
                $result['processedTca']['columns'][$column]['description'] = '';
            } elseif ('' !== $result['processedTca']['columns'][$column]['description']) {
                // If there is already a description, add a newline before our additional information
                $result['processedTca']['columns'][$column]['description'] .= PHP_EOL;
            }
            $result['processedTca']['columns'][$column]['description'] .= $this->getDescription($table, $column);
        }
        return $result;
    }

    protected function getDescription(string $table, string $column): string
    {
        $details = 'No information';
        if (!empty($this->incompatible[$table][$column])) {
            $details = implode('; ', (array)$this->incompatible[$table][$column]);
        } elseif (
            isset($this->compatible[$table][$column]['resolver'])
            && $this->compatible[$table][$column]['resolver'] instanceof Resolver
        ) {
            $resolver = $this->compatible[$table][$column]['resolver'];
            $details = sprintf("%s Tables: %s", get_class($resolver), implode(', ', $resolver->getTargetTables()));
        }
        return sprintf('[Content Publisher debug information: %s]', $details);
    }
}
