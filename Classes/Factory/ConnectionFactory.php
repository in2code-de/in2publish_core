<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Factory;

use Doctrine\DBAL\Driver\Connection;
use In2code\In2publishCore\Factory\Exception\ConnectionUnavailableException;
use In2code\In2publishCore\Utility\DatabaseUtility;

class ConnectionFactory
{
    /** @throws ConnectionUnavailableException */
    public function createLocalConnection(): Connection
    {
        $connection = DatabaseUtility::buildLocalDatabaseConnection();
        if (null === $connection) {
            throw new ConnectionUnavailableException('local');
        }
        return $connection;
    }

    /** @throws ConnectionUnavailableException */
    public function createForeignConnection(): Connection
    {
        $connection = DatabaseUtility::buildForeignDatabaseConnection();
        if (null === $connection) {
            throw new ConnectionUnavailableException('foreign');
        }
        return $connection;
    }
}
