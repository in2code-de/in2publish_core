<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter;

/**
 * @codeCoverageIgnore
 */
trait TransmissionAdapterRegistryInjection
{
    protected TransmissionAdapterRegistry $transmissionAdapterRegistry;

    /**
     * @noinspection PhpUnused
     */
    public function injectTransmissionAdapterRegistry(TransmissionAdapterRegistry $transmissionAdapterRegistry): void
    {
        $this->transmissionAdapterRegistry = $transmissionAdapterRegistry;
    }
}
