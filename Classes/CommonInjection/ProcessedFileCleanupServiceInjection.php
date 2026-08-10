<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use In2code\In2publishCore\Component\Core\Publisher\ProcessedFileCleanupService;

/**
 * @codeCoverageIgnore
 */
trait ProcessedFileCleanupServiceInjection
{
    protected ProcessedFileCleanupService $processedFileCleanupService;

    /** @noinspection PhpUnused */
    public function injectProcessedFileCleanupService(
        ProcessedFileCleanupService $processedFileCleanupService,
    ): void {
        $this->processedFileCleanupService = $processedFileCleanupService;
    }
}
