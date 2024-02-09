<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Data;

/**
 * @codeCoverageIgnore
 */
trait FalStorageTestSubjectsProviderInjection
{
    protected FalStorageTestSubjectsProvider $falStorageTestSubjectsProvider;

    /**
     * @noinspection PhpUnused
     */
    public function injectFalStorageTestSubjectProvider(
        FalStorageTestSubjectsProvider $falStorageTestSubjectsProvider
    ): void {
        $this->falStorageTestSubjectsProvider = $falStorageTestSubjectsProvider;
    }
}
