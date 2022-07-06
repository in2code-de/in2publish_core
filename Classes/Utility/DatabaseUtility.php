<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Utility;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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

use Doctrine\DBAL\Driver\Exception;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Environment\ForeignEnvironmentService;
use InvalidArgumentException;
use LogicException;
use PDO;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use ZipArchive;

use function array_map;
use function class_exists;
use function date;
use function file_put_contents;
use function filesize;
use function implode;
use function in_array;
use function is_file;
use function rtrim;
use function sprintf;
use function str_replace;
use function stripslashes;
use function time;

use const PHP_EOL;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity) Ignore for now. Refactoring will fix this.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Ignore for now. Refactoring will fix this.
 */
class DatabaseUtility
{
    protected static ?Logger $logger = null;
    protected static ?Connection $foreignConnection = null;

    /**
     * @throws Throwable
     */
    public static function buildForeignDatabaseConnection(): ?Connection
    {
        static::initializeLogger();
        if (static::$foreignConnection === null) {
            $configuration = GeneralUtility::makeInstance(ConfigContainer::class)->get('foreign.database');
            if (null === $configuration) {
                static::$logger->warning('Can not instantiate the foreign database connection without a configuration');
                static::$foreignConnection = null;
            } else {
                $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                $foreignEnvService = GeneralUtility::makeInstance(ForeignEnvironmentService::class);
                try {
                    $initCommands = $foreignEnvService->getDatabaseInitializationCommands();
                } catch (Throwable $exception) {
                    static::$logger->error(
                        'Exception in ForeignEnvironmentService. ' . $exception->getMessage(),
                        ['exception' => $exception]
                    );
                    throw $exception;
                }

                /** @noinspection PhpInternalEntityUsedInspection */
                if (!in_array('in2publish_foreign', $connectionPool->getConnectionNames(), true)) {
                    $default = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
                    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['in2publish_foreign'] = [
                        'dbname' => $configuration['name'],
                        'driver' => 'mysqli',
                        'host' => $configuration['hostname'],
                        'password' => $configuration['password'],
                        'port' => $configuration['port'],
                        'user' => $configuration['username'],
                        'charset' => $default['charset'],
                        'tableoptions' => $default['tableoptions'] ?? [],
                    ];
                }
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['in2publish_foreign']['initCommands'] = $initCommands;

                try {
                    $foreignConnection = $connectionPool->getConnectionByName('in2publish_foreign');
                    foreach ($foreignConnection->getEventManager()->getListeners() as $event => $listeners) {
                        foreach ($listeners as $listener) {
                            $foreignConnection->getEventManager()->removeEventListener($event, $listener);
                        }
                    }
                    static::$foreignConnection = $foreignConnection;
                    $foreignConnection->connect();
                } catch (Throwable $e) {
                    static::$logger->critical('Can not connect to foreign database', ['exception' => $e]);
                    static::$foreignConnection = null;
                }
            }
        }

        return static::$foreignConnection;
    }

    public static function buildLocalDatabaseConnection(): ?Connection
    {
        try {
            return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @param string $side
     *
     * @return Connection|null
     * @throws Throwable
     */
    public static function buildDatabaseConnectionForSide(string $side): ?Connection
    {
        if ($side === 'local') {
            return static::buildLocalDatabaseConnection();
        }
        if ($side === 'foreign') {
            return static::buildForeignDatabaseConnection();
        }
        throw new LogicException('Unsupported side "' . $side . '"', 1476118055);
    }

    public static function backupTable(Connection $connection, string $tableName): void
    {
        $tableName = static::sanitizeTable($connection, $tableName);
        static::initializeLogger();

        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $keepBackups = $configContainer->get('backup.publishTableCommand.keepBackups');
        $backupFolder = rtrim($configContainer->get('backup.publishTableCommand.backupLocation'), '/') . '/';
        FileUtility::cleanUpBackups($keepBackups, $tableName, $backupFolder);
        if ($keepBackups > 0) {
            static::createBackup($connection, $tableName, $backupFolder);
        } else {
            static::$logger->notice('Skipping backup for "' . $tableName . '", because keepBackups=0');
        }
    }

    /**
     * @throws Throwable
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected static function createBackup(Connection $connection, string $tableName, string $backupFolder): void
    {
        $fileName = time() . '_' . $tableName . '.sql';

        $publishTableSettings = GeneralUtility::makeInstance(ConfigContainer::class)->get('backup.publishTableCommand');
        $addDropTable = $publishTableSettings['addDropTable'];
        $zipBackup = $publishTableSettings['zipBackup'];

        /** @noinspection PhpInternalEntityUsedInspection */
        static::$logger->notice(
            'Creating a backup for "' . $tableName . '"',
            [
                'fileName' => $fileName,
                'configuration' => [
                    'addDropTable' => $addDropTable,
                    'zipBackup' => $zipBackup,
                ],
                'hostInfo' => $connection->getParams()['host'],
            ]
        );
        $data =
            PHP_EOL .
            '/*---------------------------------------------------------------' . PHP_EOL .
            '  SQL TABLE BACKUP ' . date('d.m.Y H:i:s') . ' ' . PHP_EOL .
            '  TABLE: "' . $tableName . '"' . PHP_EOL .
            '---------------------------------------------------------------*/' . PHP_EOL;

        if ($addDropTable === true) {
            $data .= 'DROP TABLE IF EXISTS ' . $tableName . ';' . PHP_EOL;
        }

        $res = $connection->executeQuery('SHOW CREATE TABLE ' . $tableName);
        $result = $res->fetchAllAssociative();

        $data .= $result[0]['Create Table'] . ';' . PHP_EOL;

        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $resultSet = $query->select('*')->from($tableName)->execute();

        while (($row = $resultSet->fetchAssociative())) {
            $data .=
                'INSERT INTO ' . $tableName . ' VALUES (' .
                implode(',', array_map([$connection, 'quote'], $row)) .
                ');' . PHP_EOL;
        }

        $backupWritten = false;

        if ($zipBackup === true) {
            if (class_exists('ZipArchive')) {
                $zipFileName = $backupFolder . $fileName . '.zip';
                $zip = new ZipArchive();
                if ($zip->open($zipFileName, ZipArchive::CREATE) === true) {
                    $zip->addFromString($fileName, $data);
                    $backupWritten = $zip->close();
                    if ($backupWritten === true) {
                        static::$logger->notice(
                            'Successfully created zip backup of "' . $tableName . '"',
                            ['fileSize' => filesize($zipFileName)]
                        );
                    }
                }
            } else {
                static::$logger->error(
                    'Error while backing up table "' . $tableName .
                    '": zipBackup is enabled but class "ZipArchive" does not exist'
                );
            }
        }

        if ($backupWritten === false) {
            $backupFile = $backupFolder . $fileName;
            if (file_put_contents($backupFolder . $fileName, $data) === false) {
                if (is_file($backupFile) === false) {
                    static::$logger->error('The backup file "' . $backupFile . '" could not be created');
                } else {
                    static::$logger->error(
                        'The backup file "' . $backupFile . '" was created but could not be written'
                    );
                }
            } else {
                static::$logger->notice(
                    'Successfully created uncompressed backup for "' . $tableName . '"',
                    [
                        'fileName' => $backupFile,
                        'fileSize' => filesize($backupFile),
                    ]
                );
            }
        }
    }

    protected static function initializeLogger(): void
    {
        if (static::$logger === null) {
            static::$logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        }
    }

    /**
     * @return mixed|string
     */
    protected static function sanitizeTable(Connection $connection, string $tableName)
    {
        $tableName = stripslashes($tableName);
        $tableName = str_replace(["'", '"'], '', $tableName);

        $allTables = $connection->getSchemaManager()->listTableNames();
        if (in_array($tableName, $allTables, true)) {
            return $tableName;
        }
        throw new InvalidArgumentException(
            sprintf(
                'The given table name was not properly escaped or does not exist. Given table name: %s',
                $tableName
            ),
            1493891084
        );
    }

    /**
     * @param Connection $fromDatabase
     * @param Connection $toDatabase
     * @param string $tableName
     *
     * @return int The number of affected rows
     *
     * @throws In2publishCoreException
     */
    public static function copyTableContents(Connection $fromDatabase, Connection $toDatabase, string $tableName): int
    {
        $rows = 0;
        if (static::truncateTable($toDatabase, $tableName)) {
            $query = $fromDatabase->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $queryResult = $query->select('*')->from($tableName)->execute();
            $rows = (int)$queryResult->rowCount();
            while ($row = $queryResult->fetchAssociative()) {
                if (1 !== static::insertRow($toDatabase, $tableName, $row)) {
                    throw new In2publishCoreException('Failed to import row into "' . $tableName . '"', 1562570305);
                }
            }
        }
        return $rows;
    }

    /**
     * Returns TRUE on success or FALSE on failure
     *
     * @param Connection $connection
     * @param string $tableName
     *
     * @return bool
     */
    protected static function truncateTable(Connection $connection, string $tableName): bool
    {
        $connection->truncate($tableName);
        return true;
    }

    /**
     * Returns the number of affected rows.
     *
     * @param Connection $connection
     * @param string $tableName
     * @param array $row
     *
     * @return int
     */
    protected static function insertRow(Connection $connection, string $tableName, array $row): int
    {
        return $connection->insert($tableName, $row);
    }

    /**
     * Copied from deprecated QueryGenerator
     * see: Deprecation-92080-DeprecatedQueryGeneratorAndQueryView.html
     *
     * Recursively fetch all descendants of a given page
     *
     * @param int $id uid of the page
     * @param int $depth
     * @param int $begin
     * @param string $permClause
     *
     * @return string comma separated list of descendant pages
     *
     * @throws Exception
     *
     * @noinspection PhpMissingParamTypeInspection
     * @SuppressWarnings(PHPMD)
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection CallableParameterUseCaseInTypeContextInspection
     */
    public static function getTreeList($id, $depth, $begin = 0, $permClause = ''): string
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin === 0) {
            $theList = $id;
        } else {
            $theList = '';
        }
        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permClause !== '') {
                $queryBuilder->andWhere(self::stripLogicalOperatorPrefix($permClause));
            }
            $statement = $queryBuilder->execute();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = self::getTreeList($row['uid'], $depth - 1, $begin - 1, $permClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }
        return (string)$theList;
    }

    /**
     * Copied from deprecated QueryHelper
     * see: Deprecation-92080-DeprecatedQueryGeneratorAndQueryView.html
     *
     * Removes the prefixes AND/OR from the input string.
     *
     * This function should be used when you can't guarantee that the string
     * that you want to use as a WHERE fragment is not prefixed.
     *
     * @param string $constraint The where part fragment with a possible leading "AND" or "OR" operator
     * @return string The modified where part without leading operator
     */
    public static function stripLogicalOperatorPrefix(string $constraint): string
    {
        return preg_replace('/^(?:(AND|OR)[[:space:]]*)+/i', '', trim($constraint)) ?: '';
    }
}
