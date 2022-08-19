<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Service;

/**
 * @codeCoverageIgnore
 */
trait TestingServiceInjection
{
    protected TestingService $testingService;

    /**
     * @noinspection PhpUnused
     */
    public function injectTestingService(TestingService $testingService): void
    {
        $this->testingService = $testingService;
    }
}
