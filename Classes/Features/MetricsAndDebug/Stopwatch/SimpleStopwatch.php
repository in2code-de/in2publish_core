<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch;

use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\Exception\StopwatchAlreadyStartedException;
use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\Exception\StopwatchWasNotStartedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\SingletonInterface;

use function date;
use function explode;
use function microtime;
use function substr;

class SimpleStopwatch implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ?float $startTime = null;

    public function start(): void
    {
        if (null !== $this->startTime) {
            throw new StopwatchAlreadyStartedException($this->startTime);
        }
        $this->startTime = microtime(true);
    }

    public function getTime(): string
    {
        if (null === $this->startTime) {
            throw new StopwatchWasNotStartedException();
        }
        $duration = microtime(true) - $this->startTime;
        [$sec, $msec] = explode('.', (string)$duration);
        return date('i:s', (int)$sec) . '.' . substr($msec, 0, 4);
    }
}
