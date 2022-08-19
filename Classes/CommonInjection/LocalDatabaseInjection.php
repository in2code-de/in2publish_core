<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Database\Connection;

/**
 * @codeCoverageIgnore
 */
trait LocalDatabaseInjection
{
    protected Connection $localDatabase;

    /**
     * @noinspection PhpUnused
     */
    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }
}
