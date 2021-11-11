<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\PublishTaskRunner;

use In2code\In2publishCore\Component\PostPublishTaskExecution\Command\RunTasksInQueueCommand as NewRunTasksInQueueCommand;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Service\Context\ContextService;

use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * @deprecated Please use \In2code\In2publishCore\Component\PostPublishTaskExecution\Command\RunTasksInQueueCommand directly
 */
class RunTasksInQueueCommand extends NewRunTasksInQueueCommand
{
    private const DEPRECATION_MESSAGE = 'The class ' . self::class . ' has been moved. Please use the new class '
                                        . parent::class . ' instead.';

    public function __construct(ContextService $contextService, TaskRepository $taskRepository, string $name = null)
    {
        trigger_error(self::DEPRECATION_MESSAGE, E_USER_DEPRECATED);
        parent::__construct($contextService, $taskRepository, $name);
    }
}
