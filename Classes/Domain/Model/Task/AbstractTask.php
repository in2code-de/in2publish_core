<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model\Task;

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task\AbstractTask as NewTaskAlias;

use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * @deprecated Please use \In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task\AbstractTask directly.
 */
abstract class AbstractTask extends NewTaskAlias
{
    private const DEPRECATION_MESSAGE = 'The class ' . self::class . ' has been moved. Please use the new class '
                                        . parent::class . ' instead.';

    // Only change to actually trigger this deprecation is the destructor
    public function __destruct()
    {
        trigger_error(self::DEPRECATION_MESSAGE, E_USER_DEPRECATED);
    }
}
