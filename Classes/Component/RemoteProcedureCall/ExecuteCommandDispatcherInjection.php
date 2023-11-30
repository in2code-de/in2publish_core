<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteProcedureCall;

/**
 * @codeCoverageIgnore
 */
trait ExecuteCommandDispatcherInjection
{
    protected ExecuteCommandDispatcher $executeCommandDispatcher;

    /**
     * @noinspection PhpUnused
     */
    public function injectExecuteCommandDispatcher(ExecuteCommandDispatcher $executeCommandDispatcher): void
    {
        $this->executeCommandDispatcher = $executeCommandDispatcher;
    }
}
