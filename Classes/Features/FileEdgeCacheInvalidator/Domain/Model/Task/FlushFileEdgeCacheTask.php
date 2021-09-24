<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Model\Task;

use In2code\In2publishCore\Domain\Model\Task\AbstractTask;
use In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Service\FileEdgeCacheInvalidationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FlushFileEdgeCacheTask extends AbstractTask
{
    protected function executeTask(): bool
    {
        $service = GeneralUtility::makeInstance(FileEdgeCacheInvalidationService::class);
        $service->flushCachesForFiles($this->configuration);
        return true;
    }
}
