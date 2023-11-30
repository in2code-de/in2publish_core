<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

/**
 * @codeCoverageIgnore
 */
trait ForeignFileInfoServiceInjection
{
    protected ForeignFileInfoService $foreignFileInfoService;

    /**
     * @noinspection PhpUnused
     */
    public function injectForeignFileInfoService(ForeignFileInfoService $foreignFileInfoService): void
    {
        $this->foreignFileInfoService = $foreignFileInfoService;
    }

}
