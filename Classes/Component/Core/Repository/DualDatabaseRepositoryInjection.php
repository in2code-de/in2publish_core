<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Repository;

/**
 * @codeCoverageIgnore
 */
trait DualDatabaseRepositoryInjection
{
    protected DualDatabaseRepository $dualDatabaseRepository;

    /**
     * @noinspection PhpUnused
     */
    public function injectDualDatabaseRepository(DualDatabaseRepository $dualDatabaseRepository): void
    {
        $this->dualDatabaseRepository = $dualDatabaseRepository;
    }
}
