<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\Exception;

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class StopwatchWasNotStartedException extends In2publishCoreException
{
    private const MESSAGE = 'The stopwatch was not started';
    public const CODE = 1658152782;

    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(self::MESSAGE, self::CODE, $previous);
    }
}
