<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling\Service;

/**
 * @codeCoverageIgnore
 */
trait FileSystemInfoServiceInjection
{
    protected FileSystemInfoService $fileSystemInfoService;

    /**
     * @noinspection PhpUnused
     */
    public function injectFileSystemInfoService(FileSystemInfoService $fileSystemInfoService): void
    {
        $this->fileSystemInfoService = $fileSystemInfoService;
    }
}
