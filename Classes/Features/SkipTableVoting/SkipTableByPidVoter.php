<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SkipTableVoting;

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

use Doctrine\DBAL\Exception;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository as CR;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;

use function array_column;
use function array_key_exists;

class SkipTableByPidVoter implements SingletonInterface
{
    /** @var array */
    protected $pidIndex = [];

    /** @var Connection */
    protected $localConnection;

    /** @var Connection */
    protected $foreignConnection;

    public function __construct()
    {
        $this->localConnection = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignConnection = DatabaseUtility::buildForeignDatabaseConnection();
    }

    public function shouldSkipSearchingForRelatedRecordByTable(array $votes, CR $repository, array $arguments): array
    {
        /** @var string $table */
        $table = $arguments['tableName'];
        /** @var RecordInterface $record */
        $record = $arguments['record'];
        /** @var int $pid */
        $pid = $record->getIdentifier();

        if ($this->shouldSkipTableByPid($table, $pid)) {
            $votes['yes']++;
        }
        return [$votes, $repository, $arguments];
    }

    protected function shouldSkipTableByPid(string $table, int $pid): bool
    {
        if (!array_key_exists($table, $this->pidIndex)) {
            $this->pidIndex[$table] = [];
            foreach ([$this->localConnection, $this->foreignConnection] as $connection) {
                if (null === $connection) {
                    throw new \Exception('Database connection error');
                }
                $rows = $this->fetchAllPids($connection, $table);
                foreach ($rows as $existingPid) {
                    $this->pidIndex[$table][$existingPid] = $existingPid;
                }
            }
        }

        return !array_key_exists($pid, $this->pidIndex[$table]);
    }

    protected function fetchAllPids(Connection $connection, string $table): array
    {
        $quotedTable = $connection->quoteIdentifier($table);
        try {
            $rows = $connection->executeQuery('SELECT DISTINCT pid FROM ' . $quotedTable)->fetchAll();
        } catch (Exception $e) {
            // The exception might indicate that there is no PID field. Ignore it.
            return [];
        }
        return array_column($rows, 'pid');
    }
}
