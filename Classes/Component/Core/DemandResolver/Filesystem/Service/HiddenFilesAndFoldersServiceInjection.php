<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

/**
 * @codeCoverageIgnore
 */
trait HiddenFilesAndFoldersServiceInjection
{
    protected HiddenFilesAndFoldersService $hiddenFilesAndFoldersService;

    /**
     * @noinspection PhpUnused
     */
    public function injectHiddenFilesAndFoldersService(
        HiddenFilesAndFoldersService $hiddenFilesAndFoldersService
    ): void {
        $this->hiddenFilesAndFoldersService = $hiddenFilesAndFoldersService;
    }
}
