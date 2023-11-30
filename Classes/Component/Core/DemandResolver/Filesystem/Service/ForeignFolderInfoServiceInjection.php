<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

/**
 * @codeCoverageIgnore
 */
trait ForeignFolderInfoServiceInjection
{
    protected ForeignFolderInfoService $foreignFolderInfoService;

    /**
     * @noinspection PhpUnused
     */
    public function injectForeignFolderInfoService(ForeignFolderInfoService $foreignFolderInfoService): void
    {
        $this->foreignFolderInfoService = $foreignFolderInfoService;
    }
}
