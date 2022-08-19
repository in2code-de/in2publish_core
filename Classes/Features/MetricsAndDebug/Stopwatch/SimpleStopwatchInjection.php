<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch;

/**
 * @codeCoverageIgnore
 */
trait SimpleStopwatchInjection
{
    protected SimpleStopwatch $simpleStopwatch;

    /**
     * @noinspection PhpUnused
     */
    public function injectSimpleStopwatch(SimpleStopwatch $simpleStopwatch): void
    {
        $this->simpleStopwatch = $simpleStopwatch;
    }
}
