<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Extension;

/**
 * @codeCoverageIgnore
 */
trait ExtensionServiceInjection
{
    protected ExtensionService $extensionService;

    /**
     * @noinspection PhpUnused
     */
    public function injectExtensionService(ExtensionService $extensionService): void
    {
        $this->extensionService = $extensionService;
    }
}
