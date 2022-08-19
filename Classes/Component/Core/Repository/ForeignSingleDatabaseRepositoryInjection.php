<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Repository;

/**
 * @codeCoverageIgnore
 */
trait ForeignSingleDatabaseRepositoryInjection
{
    protected SingleDatabaseRepository $foreignRepository;

    /**
     * @noinspection PhpUnused
     */
    public function injectForeignSingleDatabaseRepository(SingleDatabaseRepository $foreignRepository): void
    {
        $this->foreignRepository = $foreignRepository;
    }
}
