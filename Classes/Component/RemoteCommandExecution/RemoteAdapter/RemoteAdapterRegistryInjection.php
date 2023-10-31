<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter;

/**
 * @codeCoverageIgnore
 */
trait RemoteAdapterRegistryInjection
{
    protected RemoteAdapterRegistry $remoteAdapterRegistry;

    /**
     * @noinspection PhpUnused
     */
    public function injectRemoteAdapterRegistry(RemoteAdapterRegistry $remoteAdapterRegistry): void
    {
        $this->remoteAdapterRegistry = $remoteAdapterRegistry;
    }
}
