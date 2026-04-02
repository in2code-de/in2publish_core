<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * @codeCoverageIgnore
 */
trait StorageRepositoryInjection
{
    protected StorageRepository $storageRepository;

    /**
     * @noinspection PhpUnused
     */
    public function injectStorageRepository(StorageRepository $storageRepository): void
    {
        $this->storageRepository = $storageRepository;
    }
}