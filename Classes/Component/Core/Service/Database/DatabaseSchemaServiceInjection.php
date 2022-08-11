<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Service\Database;

/**
 * @codeCoverageIgnore
 */
trait DatabaseSchemaServiceInjection
{
    protected DatabaseSchemaService $databaseSchemaService;

    /**
     * @noinspection PhpUnused
     */
    public function injectDatabaseSchemaService(DatabaseSchemaService $databaseSchemaService): void
    {
        $this->databaseSchemaService = $databaseSchemaService;
    }
}
