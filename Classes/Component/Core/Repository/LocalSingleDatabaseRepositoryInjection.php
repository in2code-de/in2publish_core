<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Repository;

/**
 * @codeCoverageIgnore
 */
trait LocalSingleDatabaseRepositoryInjection
{
    protected SingleDatabaseRepository $localRepository;

    /**
     * @noinspection PhpUnused
     */
    public function injectLocalSingleDatabaseRepository(SingleDatabaseRepository $localRepository): void
    {
        $this->localRepository = $localRepository;
    }
}
