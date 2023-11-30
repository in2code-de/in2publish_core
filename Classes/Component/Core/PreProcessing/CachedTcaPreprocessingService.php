<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing;

use __PHP_Incomplete_Class;
use In2code\In2publishCore\CommonInjection\CacheInjection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_column;

class CachedTcaPreprocessingService
{
    use CacheInjection;

    /**
     * Stores the part of the TCA that can be used for relation resolving
     *
     * @var array<array|null>
     */
    protected array $compatibleTca;
    /**
     * Stores the part of the TCA that can not be used for relation resolving including reasons
     *
     * @var array[]
     */
    protected array $incompatibleTca;

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

    protected function initialize(): void
    {
        if (isset($this->compatibleTca, $this->incompatibleTca)) {
            return;
        }

        if ($this->cache->has('tca_preprocessing_results')) {
            $cacheEntry = $this->cache->get('tca_preprocessing_results');
            if ($this->isCacheValid($cacheEntry['compatibleTca'])) {
                $this->compatibleTca = $cacheEntry['compatibleTca'];
                $this->incompatibleTca = $cacheEntry['incompatibleTca'];
                return;
            }
        }

        $tcaPreProcessingService = GeneralUtility::makeInstance(TcaPreProcessingService::class);
        $this->compatibleTca = $tcaPreProcessingService->getCompatibleTcaParts();
        $this->incompatibleTca = $tcaPreProcessingService->getIncompatibleTcaParts();

        $cacheEntry = [
            'compatibleTca' => $this->compatibleTca,
            'incompatibleTca' => $this->incompatibleTca,
        ];
        $this->cache->set('tca_preprocessing_results', $cacheEntry);
    }

    protected function isCacheValid(array $compatibleTca): bool
    {
        foreach ($compatibleTca as $properties) {
            foreach (array_column($properties, 'resolver') as $resolver) {
                if ($resolver instanceof __PHP_Incomplete_Class) {
                    $this->cache->remove('tca_preprocessing_results');
                    return false;
                }
            }
        }
        return true;
    }
}
