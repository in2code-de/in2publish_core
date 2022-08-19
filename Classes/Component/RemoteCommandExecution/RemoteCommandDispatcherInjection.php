<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteCommandExecution;

/**
 * @codeCoverageIgnore
 */
trait RemoteCommandDispatcherInjection
{
    protected RemoteCommandDispatcher $remoteCommandDispatcher;

    /**
     * @noinspection PhpUnused
     */
    public function injectRemoteCommandDispatcher(RemoteCommandDispatcher $remoteCommandDispatcher): void
    {
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }
}
