<?php
namespace In2code\In2publishCore\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Doctrine\DBAL\DBALException;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Service\Environment\ForeignEnvironmentService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DatabaseUtility
 */
class DatabaseUtility
{
    /**
     * @var Logger
     */
    protected static $logger = null;

    /**
     * @var Connection
     */
    protected static $foreignConnection = null;

    /**
     * @return Connection
     * @throws DBALException
     */
    public static function buildForeignDatabaseConnection()
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
                $initCommands = $foreignEnvService->getDatabaseInitializationCommands();

                if (!in_array('in2publish_foreign', $connectionPool->getConnectionNames())) {
                    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['in2publish_foreign'] = [
                        'dbname' => $configuration['name'],
                        'driver' => 'mysqli',
                        'host' => $configuration['hostname'],
                        'password' => $configuration['password'],
                        'port' => $configuration['port'],
                        'user' => $configuration['username'],
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
                } catch (DBALException $e) {
                    static::$foreignConnection = null;
                }
            }
        }

        return static::$foreignConnection;
    }

    /**
     * @param $string
     * @return string
     */
    public static function quoteString($string)
    {
        return static::buildLocalDatabaseConnection()->quote($string);
    }

    /**
     * @return null|Connection
     */
    public static function buildLocalDatabaseConnection()
    {
        try {
            return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        } catch (DBALException $e) {
            return null;
        }
    }

    /**
     * @param $side
     * @return null|Connection
     * @throws DBALException
     */
    public static function buildDatabaseConnectionForSide($side)
    {
        if ($side === 'local') {
            return static::buildLocalDatabaseConnection();
        } elseif ($side === 'foreign') {
            return static::buildForeignDatabaseConnection();
        } else {
            throw new \LogicException('Unsupported side "' . $side . '"', 1476118055);
        }
    }

    /**
     * @param Connection $connection
     * @param string $tableName
     * @throws DBALException
     */
    public static function backupTable(Connection $connection, $tableName)
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
     * Check if a table is existing on local database
     *
     * @param string $tableName
     * @return bool
     */
    public static function isTableExistingOnLocal($tableName)
    {
        $allTables = static::buildLocalDatabaseConnection()->admin_get_tables();
        return array_key_exists($tableName, $allTables);
    }

    /**
     * @param Connection $connection
     * @param $tableName
     * @param $backupFolder
     * @throws DBALException
     */
    protected static function createBackup(Connection $connection, $tableName, $backupFolder)
    {
        $fileName = time() . '_' . $tableName . '.sql';

        $publishTableSettings = GeneralUtility::makeInstance(ConfigContainer::class)->get('backup.publishTableCommand');
        $addDropTable = $publishTableSettings['addDropTable'];
        $zipBackup = $publishTableSettings['zipBackup'];

        static::$logger->notice(
            'Creating a backup for "' . $tableName . '"',
            [
                'fileName' => $fileName,
                'configuration' => [
                    'addDropTable' => $addDropTable,
                    'zipBackup' => $zipBackup,
                ],
                'hostInfo' => $connection->getHost(),
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

        $res = $connection->query('SHOW CREATE TABLE ' . $tableName);
        $result = $res->fetchAll();

        $data .= $result[0]['Create Table'] . ';' . PHP_EOL;

        $resultSet = $connection->select(['*'], $tableName);

        while (($row = $resultSet->fetch())) {
            $data .=
                'INSERT INTO ' . $tableName . ' VALUES (' .
                implode(',', array_map([$connection, 'quote'], $row)) .
                ');' . PHP_EOL;
        }

        $backupWritten = false;

        if ($zipBackup === true) {
            if (class_exists('ZipArchive')) {
                $zipFileName = $backupFolder . $fileName . '.zip';
                $zip = new \ZipArchive();
                if ($zip->open($zipFileName, \ZipArchive::CREATE) === true) {
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

    /**
     * @return void
     */
    protected static function initializeLogger()
    {
        if (static::$logger === null) {
            static::$logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(
                get_called_class()
            );
        }
    }

    /**
     * @param Connection $connection
     * @param string $tableName
     * @return mixed|string
     */
    protected static function sanitizeTable(Connection $connection, $tableName)
    {
        $tableName = stripslashes($tableName);
        $tableName = str_replace("'", '', $tableName);
        $tableName = str_replace('"', '', $tableName);

        $allTables = $connection->getSchemaManager()->listTables();
        foreach ($allTables as $table) {
            if ($table->getName() === $tableName) {
                return $tableName;
            }
        }
        throw new \InvalidArgumentException(
            sprintf(
                'The given table name was not properly escaped or does not exist. Given table name: %s',
                $tableName
            ),
            1493891084
        );
    }
}
