<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

use TYPO3\CMS\Core\Database\Connection;

/**
 * @codeCoverageIgnore
 */
trait ForeignDatabaseReconnectedInjection
{
    protected Connection $foreignDatabase;

    /**
     * @noinspection PhpUnused
     */
    public function injectForeignDatabase(Connection $foreignDatabase): void
    {
        $this->foreignDatabase = clone $foreignDatabase;
        $this->foreignDatabase->close();
        $this->foreignDatabase->connect();
    }
}
