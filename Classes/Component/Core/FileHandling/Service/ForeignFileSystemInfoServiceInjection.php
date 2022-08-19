<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling\Service;

/**
 * @codeCoverageIgnore
 */
trait ForeignFileSystemInfoServiceInjection
{
    protected ForeignFileSystemInfoService $foreignFileSystemInfoService;

    /**
     * @noinspection PhpUnused
     */
    public function injectForeignFileSystemInfoService(ForeignFileSystemInfoService $foreignFileSystemInfoService): void
    {
        $this->foreignFileSystemInfoService = $foreignFileSystemInfoService;
    }
}
