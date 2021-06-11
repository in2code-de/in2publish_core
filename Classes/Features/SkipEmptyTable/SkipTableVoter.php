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
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_column;
use function array_combine;
use function array_diff;
use function array_key_exists;
use function array_keys;
use function in_array;

class SkipTableVoter implements SingletonInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var null|array */
    protected $tablesToSkip = null;

    protected $statistics = [
        'skipped' => [],
    ];

    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
    }

    public function shouldSkipSearchingForRelatedRecordsByProperty(
        array $votes,
        CommonRepository $repository,
        array $arguments
    ): array {
        $this->initialize();
        $config = $arguments['columnConfiguration'];
        if (
            empty($config['type'])
            || !in_array($config['type'], ['select', 'group', 'inline'])
        ) {
            return [$votes, $repository, $arguments];
        }

        if (
            (array_key_exists('MM', $config) && isset($this->tablesToSkip[$config['MM']]))
            || (array_key_exists('foreign_table', $config) && isset($this->tablesToSkip[$config['foreign_table']]))
            || $this->isGroupDbWhereAllAllowedTablesAreEmpty($config)
        ) {
            $this->statistics['skipped'][__FUNCTION__]++;
            $votes['yes']++;
        }
        return [$votes, $repository, $arguments];
    }

    protected function isGroupDbWhereAllAllowedTablesAreEmpty(array $columnConfiguration): bool
    {
        if (
            'group' === $columnConfiguration['type']
            && 'db' === ($columnConfiguration['internal_type'] ?? 'none')
            && array_key_exists('allowed', $columnConfiguration)
        ) {
            $tables = GeneralUtility::trimExplode(',', $columnConfiguration['allowed']);
            if (!in_array('*', $tables)) {
                $diff = array_diff($tables, $this->tablesToSkip);
                if (empty($diff)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function shouldSkipFindByIdentifier(array $votes, CommonRepository $repository, array $arguments): array
    {
        return $this->shouldSkipByTablename($votes, $repository, $arguments, __FUNCTION__);
    }

    public function shouldSkipFindByProperty(array $votes, CommonRepository $repository, array $arguments): array
    {
        return $this->shouldSkipByTablename($votes, $repository, $arguments, __FUNCTION__);
    }

    public function shouldSkipSearchingForRelatedRecordByTable(
        array $votes,
        CommonRepository $repository,
        array $arguments
    ): array {
        return $this->shouldSkipByTablename($votes, $repository, $arguments, __FUNCTION__);
    }

    protected function shouldSkipByTablename(
        array $votes,
        CommonRepository $repository,
        array $arguments,
        string $function
    ): array {
        $this->initialize();
        $table = $arguments['tableName'];
        if (isset($this->tablesToSkip[$table])) {
            $this->statistics['skipped'][$function]++;
            $votes['yes']++;
        }
        return [$votes, $repository, $arguments];
    }

    /**
     * Identify all tables which are empty.
     * "SELECT 1 FROM table" returns "1" if at least one row exists.
     * This is the most efficient way to check if a table is empty.
     */
    protected function initialize(): void
    {
        if (null !== $this->tablesToSkip) {
            return;
        }
        $allTables = $this->identifyAllTablesInTCA();

        $localConnection = DatabaseUtility::buildLocalDatabaseConnection();
        $foreignConnection = DatabaseUtility::buildForeignDatabaseConnection();
        if (null === $localConnection || null === $foreignConnection) {
            return;
        }
        foreach ($allTables as $table) {
            if ($this->isTableEmpty($localConnection, $table) && $this->isTableEmpty($foreignConnection, $table)) {
                $this->tablesToSkip[$table] = $table;
            }
        }
    }

    protected function isTableEmpty(Connection $connection, string $table): bool
    {
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

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function identifyAllTablesInTCA()
    {
        $allTables = array_keys($GLOBALS['TCA']);
        $allTables = array_combine($allTables, $allTables);
        $condensedTca = array_combine($allTables, array_column($GLOBALS['TCA'], 'columns'));
        foreach ($condensedTca as $columns) {
            foreach (array_column($columns, 'config') as $config) {
                $type = $config['type'] ?? 'none';
                if ($type === 'select' && !empty($config['MM'])) {
                    $allTables[$config['MM']] = $config['MM'];
                }
            }
        }
        return $allTables;
    }

    public function __destruct()
    {
        $this->logger->debug('SkipTableVoter statistics', $this->statistics);
    }
}
