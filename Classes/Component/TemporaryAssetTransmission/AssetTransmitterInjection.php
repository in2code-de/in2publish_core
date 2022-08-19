<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TemporaryAssetTransmission;

/**
 * @codeCoverageIgnore
 */
trait AssetTransmitterInjection
{
    protected AssetTransmitter $assetTransmitter;

    /**
     * @noinspection PhpUnused
     */
    public function injectAssetTransmitter(AssetTransmitter $assetTransmitter): void
    {
        $this->assetTransmitter = $assetTransmitter;
    }
}
