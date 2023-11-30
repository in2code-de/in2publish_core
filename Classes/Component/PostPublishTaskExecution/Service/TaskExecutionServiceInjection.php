<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\PostPublishTaskExecution\Service;

/**
 * @codeCoverageIgnore
 */
trait TaskExecutionServiceInjection
{
    protected TaskExecutionService $taskExecutionService;

    /**
     * @noinspection PhpUnused
     */
    public function injectTaskExecutionService(TaskExecutionService $taskExecutionService): void
    {
        $this->taskExecutionService = $taskExecutionService;
    }
}
