<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\Exception;

use DateTime;
use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function sprintf;

/**
 * @codeCoverageIgnore
 */
class StopwatchAlreadyStartedException extends In2publishCoreException
{
    private const MESSAGE = 'The stopwatch was already started at %s (%.4f)';
    public const CODE = 1658152739;
    private float $firstStart;

    public function __construct(float $firstStart, ?Throwable $previous = null)
    {
        $this->firstStart = $firstStart;
        $readableDate = DateTime::createFromFormat('U.u', (string)$firstStart)->format('Y-m-d H:i:s.u');
        parent::__construct(sprintf(self::MESSAGE, $readableDate, $firstStart), self::CODE, $previous);
    }

    public function getFirstStart(): float
    {
        return $this->firstStart;
    }
}
