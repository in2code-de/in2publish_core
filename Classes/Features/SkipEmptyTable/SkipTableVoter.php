<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SkipEmptyTable;

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
use In2code\In2publishCore\Event\VoteIfFindingByIdentifierShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfFindingByPropertyShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function in_array;
use function ksort;

class SkipTableVoter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var Connection */
    protected $localDatabase;

    /** @var Connection */
    protected $foreignDatabase;

    /** @var array<string, bool> */
    protected $tables = [];

    /** @var array<string, array<string, int>> */
    protected $statistics = [
        'query' => [],
        'skip' => [],
    ];

    public function __construct(Connection $localDatabase, Connection $foreignDatabase)
    {
        $this->localDatabase = $localDatabase;
        $this->foreignDatabase = $foreignDatabase;
    }

    public function shouldSkipSearchingForRelatedRecordsByProperty(
        VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped $event
    ): void {
        $config = $event->getColumnConfiguration();
        if (empty($config['type']) || !in_array($config['type'], ['select', 'group', 'inline'])) {
            return;
        }

        if (array_key_exists('MM', $config) && $this->isEmptyTable($config['MM'])) {
            $this->statistics['skip'][$config['MM']]++;
            $event->voteYes();
        } elseif (array_key_exists('foreign_table', $config) && $this->isEmptyTable($config['foreign_table'])) {
            $this->statistics['skip'][$config['foreign_table']]++;
            $event->voteYes();
        } elseif ($this->isGroupDbWhereAllAllowedTablesAreEmpty($config)) {
            $this->statistics['skip'][$config['allowed']]++;
            $event->voteYes();
        }
    }

    public function shouldSkipFindByIdentifier(VoteIfFindingByIdentifierShouldBeSkipped $event): void
    {
        $table = $event->getTableName();
        if ($this->isEmptyTable($table)) {
            $this->statistics['skip'][$table]++;
            $event->voteYes();
        }
    }

    public function shouldSkipFindByProperty(VoteIfFindingByPropertyShouldBeSkipped $event): void
    {
        $table = $event->getTableName();
        if ($this->isEmptyTable($table)) {
            $this->statistics['skip'][$table]++;
            $event->voteYes();
        }
    }

    public function shouldSkipFindByTable(VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped $event): void
    {
        $table = $event->getTableName();
        if ($this->isEmptyTable($table)) {
            $this->statistics['skip'][$table]++;
            $event->voteYes();
        }
    }

    protected function isGroupDbWhereAllAllowedTablesAreEmpty(array $config): bool
    {
        if (
            'group' === $config['type']
            && 'db' === ($config['internal_type'] ?? 'none')
            && array_key_exists('allowed', $config)
        ) {
            $tables = GeneralUtility::trimExplode(',', $config['allowed']);
            foreach ($tables as $table) {
                if ('*' === $table) {
                    return false;
                }
                if (!$this->isEmptyTable($table)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    protected function isEmptyTable(string $table): bool
    {
        if (!array_key_exists($table, $this->tables)) {
            $this->tables[$table] = (
                $this->isEmpty($this->localDatabase, $table)
                && $this->isEmpty($this->foreignDatabase, $table)
            );
        }
        return $this->tables[$table];
    }

    protected function isEmpty(Connection $connection, string $table): bool
    {
        $this->statistics['query'][$table]++;
        try {
            $query = 'SELECT 1 FROM ' . $connection->quoteIdentifier($table) . ';';
            $exists = $connection->executeQuery($query)->fetchColumn();
            if (false === $exists) {
                return true;
            }
        } catch (Exception $exception) {
            // Ignore any errors.
            // They might indicate, that the table does not exists, but that's not this classes' responsibility
        }
        return false;
    }

    public function __destruct()
    {
        ksort($this->statistics['skip']);
        ksort($this->statistics['query']);
        $this->logger->debug('SkipTableVoter statistics', $this->statistics);
    }
}
