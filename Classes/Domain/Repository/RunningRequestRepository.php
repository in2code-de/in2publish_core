<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Repository;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
 * Christine Zoglmeier <christine.zoglmeier@in2code.de>
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

use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;

class RunningRequestRepository
{
    public const RUNNING_REQUEST_TABLE_NAME = 'tx_in2publishcore_running_request';

    /** @var Connection */
    protected $connection;

    protected $inserts = [];

    protected $rtc = [];

    public function __construct()
    {
        $this->connection = DatabaseUtility::buildLocalDatabaseConnection();
    }

    public function add(string $recordId, string $tableName, string $token): void
    {
        $uniqueKey = $tableName . '/' . $recordId;
        $this->inserts[$uniqueKey] = [
            'uid' => null,
            'record_id' => $recordId,
            'table_name' => $tableName,
            'request_token' => $token,
            'timestamp_begin' => $GLOBALS['EXEC_TIME'],
        ];
    }

    public function flush(): void
    {
        if (!empty($this->inserts)) {
            foreach (array_chunk($this->inserts, 1000) as $chunk) {
                $this->connection->bulkInsert(self::RUNNING_REQUEST_TABLE_NAME, $chunk);
            }
            $this->inserts = [];
        }
    }

    /**
     * @param int|string $identifier
     */
    public function isPublishingInDifferentRequest($identifier, string $tableName, string $token): bool
    {
        if (!isset($this->rtc['content'])) {
            $query = $this->connection->createQueryBuilder();
            $query->select('*')
                  ->from(self::RUNNING_REQUEST_TABLE_NAME)
                  ->where($query->expr()->neq('request_token', $query->createNamedParameter($token)));
            $result = $query->execute();
            foreach ($result->fetchAll() as $row) {
                $this->rtc['content'][$row['table_name']][$row['record_id']] = true;
            }
        }
        return isset($this->rtc['content'][$tableName][$identifier]);
    }

    public function deleteAllByToken(string $token): void
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete(self::RUNNING_REQUEST_TABLE_NAME)
              ->where($query->expr()->eq('request_token', $query->createNamedParameter($token)))
              ->execute();
    }
}
