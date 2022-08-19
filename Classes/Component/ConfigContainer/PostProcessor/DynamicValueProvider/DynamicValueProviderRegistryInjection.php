<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\PostProcessor\DynamicValueProvider;

/**
 * @codeCoverageIgnore
 */
trait DynamicValueProviderRegistryInjection
{
    protected DynamicValueProviderRegistry $dynamicValueProviderRegistry;

    /**
     * @noinspection PhpUnused
     */
    public function injectDynamicValueProviderRegistry(DynamicValueProviderRegistry $dynamicValueProviderRegistry): void
    {
        $this->dynamicValueProviderRegistry = $dynamicValueProviderRegistry;
    }
}
