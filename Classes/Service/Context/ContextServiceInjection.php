<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Context;

/**
 * @codeCoverageIgnore
 */
trait ContextServiceInjection
{
    protected ContextService $contextService;

    /**
     * @noinspection PhpUnused
     */
    public function injectContextService(ContextService $contextService): void
    {
        $this->contextService = $contextService;
    }
}
