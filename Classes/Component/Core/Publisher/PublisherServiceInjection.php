<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

/**
 * @codeCoverageIgnore
 */
trait PublisherServiceInjection
{
    protected PublisherService $publisherService;

    /**
     * @noinspection PhpUnused
     */
    public function injectPublisherService(PublisherService $publisherService): void
    {
        $this->publisherService = $publisherService;
    }
}
