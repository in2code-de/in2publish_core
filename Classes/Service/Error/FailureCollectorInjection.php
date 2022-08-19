<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Error;

/**
 * @codeCoverageIgnore
 */
trait FailureCollectorInjection
{
    protected FailureCollector $failureCollector;

    /**
     * @noinspection PhpUnused
     */
    public function injectFailureCollector(FailureCollector $failureCollector): void
    {
        $this->failureCollector = $failureCollector;
    }
}
