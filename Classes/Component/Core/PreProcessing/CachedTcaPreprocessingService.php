<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing;

use In2code\In2publishCore\CommonInjection\CacheInjection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
            $this->compatibleTca = $cacheEntry['compatibleTca'];
            $this->incompatibleTca = $cacheEntry['incompatibleTca'];
            return;
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
}
