<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter;

/**
 * @codeCoverageIgnore
 */
trait RemoteAdapterInjection
{
    protected AdapterInterface $remoteAdapter;

    /**
     * @noinspection PhpUnused
     */
    public function injectTransmissionAdapter(AdapterInterface $remoteAdapter): void
    {
        $this->remoteAdapter = $remoteAdapter;
    }
}
