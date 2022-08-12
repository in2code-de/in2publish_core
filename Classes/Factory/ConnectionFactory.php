<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Factory;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use Doctrine\DBAL\Driver\Connection;
use In2code\In2publishCore\Factory\Exception\ConnectionUnavailableException;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use In2code\In2publishCore\Utility\DatabaseUtility;

class ConnectionFactory
{
    use ContextServiceInjection;

    /**
     * @throws ConnectionUnavailableException
     */
    public function createLocalConnection(): Connection
    {
        $connection = DatabaseUtility::buildLocalDatabaseConnection();
        if (null === $connection) {
            throw new ConnectionUnavailableException('local');
        }
        return $connection;
    }

    /**
     * @throws ConnectionUnavailableException
     */
    public function createForeignConnection(): Connection
    {
        $connection = DatabaseUtility::buildForeignDatabaseConnection();
        if (null === $connection) {
            throw new ConnectionUnavailableException('foreign');
        }
        return $connection;
    }

    /**
     * @throws ConnectionUnavailableException
     */
    public function createOtherConnection(): Connection
    {
        if ($this->contextService->isLocal()) {
            return $this->createForeignConnection();
        }
        return $this->createLocalConnection();
    }
}
