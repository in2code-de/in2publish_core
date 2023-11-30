<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

/**
 * @codeCoverageIgnore
 */
trait FileInfoServiceInjection
{
    protected FileInfoService $fileInfoService;

    /**
     * @noinspection PhpUnused
     */
    public function injectFileInfoService(FileInfoService $fileInfoService): void
    {
        $this->fileInfoService = $fileInfoService;
    }
}
