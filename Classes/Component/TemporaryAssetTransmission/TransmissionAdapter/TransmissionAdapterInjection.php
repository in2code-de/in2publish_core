<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter;

/**
 * @codeCoverageIgnore
 */
trait TransmissionAdapterInjection
{
    protected AdapterInterface $transmissionAdapter;

    /**
     * @noinspection PhpUnused
     */
    public function injectTransmissionAdapter(AdapterInterface $transmissionAdapter): void
    {
        $this->transmissionAdapter = $transmissionAdapter;
    }
}
