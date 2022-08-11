<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository;

/**
 * @codeCoverageIgnore
 */
trait TaskRepositoryInjection
{
    protected TaskRepository $taskRepository;

    /**
     * @noinspection PhpUnused
     */
    public function injectTaskRepository(TaskRepository $taskRepository): void
    {
        $this->taskRepository = $taskRepository;
    }
}
