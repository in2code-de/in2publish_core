<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

/**
 * @codeCoverageIgnore
 */
trait LocalFolderInfoServiceInjection
{
    protected LocalFolderInfoService $localFolderInfoService;

    /**
     * @noinspection PhpUnused
     */
    public function injectLocalFolderInfoService(LocalFolderInfoService $localFolderInfoService): void
    {
        $this->localFolderInfoService = $localFolderInfoService;
    }
}
