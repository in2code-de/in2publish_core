<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Factory;

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Factory\TaskFactory as NewTaskFactory;

use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * @deprecated Please use \In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Factory\TaskFactory
 *     directly.
 */
class TaskFactory extends NewTaskFactory
{
    private const DEPRECATION_MESSAGE = 'The class ' . self::class . ' has been moved. Please use the new class '
                                        . parent::class . ' instead.';

    public function __construct()
    {
        trigger_error(self::DEPRECATION_MESSAGE, E_USER_DEPRECATED);
    }
}
