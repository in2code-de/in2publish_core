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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Log\Logger;
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
     * @var DatabaseConnection
     */
    protected static $foreignDatabase = null;

    /**
     * @return DatabaseConnection
     */
    public static function buildForeignDatabaseConnection()
    {
        self::initializeLogger();
        if (self::$foreignDatabase === null) {
            $configuration = ConfigurationUtility::getConfiguration('database.foreign');
            /** @var DatabaseConnection $foreignDatabase */
            self::$foreignDatabase = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
            self::$foreignDatabase->setDatabaseHost($configuration['hostname']);
            self::$foreignDatabase->setDatabaseName($configuration['name']);
            self::$foreignDatabase->setDatabasePassword($configuration['password']);
            self::$foreignDatabase->setDatabaseUsername($configuration['username']);
            self::$foreignDatabase->setDatabasePort($configuration['port']);
            try {
                @self::$foreignDatabase->connectDB();
            } catch (\Exception $e) {
                self::$logger->error($e->getMessage());
                self::$foreignDatabase = null;
            }
        }

        return self::$foreignDatabase;
    }

    /**
     * @param string $string
     * @param string $tableName
     * @return string
     */
    public static function quoteString($string, $tableName)
    {
        return self::buildLocalDatabaseConnection()->quoteStr($string, $tableName);
    }

    /**
     * @return DatabaseConnection
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function buildLocalDatabaseConnection()
    {
        return isset($GLOBALS['TYPO3_DB']) ? $GLOBALS['TYPO3_DB'] : null;
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @param string $tableName
     * @throws \Exception
     */
    public static function backupTable(DatabaseConnection $databaseConnection, $tableName)
    {
        self::initializeLogger();

        if (ConfigurationUtility::getLoadingState() !== ConfigurationUtility::STATE_LOADED) {
            $message =
                'BackupTable was called in context "' . getenv('IN2PUBLISH_CONTEXT')
                . '", but the configuration was not loaded successfully. Aborting immediately to prevent any damage.';
            self::$logger->critical($message);
            throw new \Exception($message, 1446819956);
        }

        $keepBackups = (int)ConfigurationUtility::getConfiguration('backup.publishTableCommand.keepBackups');
        $backupFolder =
            rtrim(ConfigurationUtility::getConfiguration('backup.publishTableCommand.backupLocation'), '/')
            . '/';
        FileUtility::cleanUpBackups($keepBackups, $tableName, $backupFolder);
        if ($keepBackups > 0) {
            self::createBackup($databaseConnection, $tableName, $backupFolder);
        } else {
            self::$logger->notice('Skipping backup for "' . $tableName . '", because keepBackups=0');
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
        $allTables = self::buildLocalDatabaseConnection()->admin_get_tables();
        return array_key_exists($tableName, $allTables);
    }

    /**
     * @param DatabaseConnection $databaseConnection
     * @param string $tableName
     * @param string $backupFolder
     * @return void
     */
    protected static function createBackup(DatabaseConnection $databaseConnection, $tableName, $backupFolder)
    {

        $fileName = time() . '_' . $tableName . '.sql';

        $publishTableSettings = ConfigurationUtility::getConfiguration('backup.publishTableCommand');
        $addDropTable = $publishTableSettings['addDropTable'];
        $zipBackup = $publishTableSettings['zipBackup'];

        self::$logger->notice(
            'Creating a backup for "' . $tableName . '"',
            array(
                'fileName' => $fileName,
                'configuration' => array(
                    'addDropTable' => $addDropTable,
                    'zipBackup' => $zipBackup,
                ),
                'hostInfo' => $databaseConnection->getDatabaseHandle()->host_info,
            )
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

        $res = $databaseConnection->admin_query('SHOW CREATE TABLE ' . $tableName);
        $result = $res->fetch_row();

        $data .= $result[1] . ';' . PHP_EOL;

        $resultSet = $databaseConnection->exec_SELECTquery('*', $tableName, '1=1');

        while (($row = $databaseConnection->sql_fetch_assoc($resultSet))) {
            $data .=
                'INSERT INTO ' . $tableName . ' VALUES (' .
                implode(',', $databaseConnection->fullQuoteArray($row, $tableName)) .
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
                        self::$logger->notice(
                            'Successfully created zip backup of "' . $tableName . '"',
                            array('fileSize' => filesize($zipFileName))
                        );
                    }
                }
            } else {
                self::$logger->error(
                    'Error while backing up table "' . $tableName .
                    '": zipBackup is enabled but class "ZipArchive" does not exist'
                );
            }
        }

        if ($backupWritten === false) {
            $backupFile = $backupFolder . $fileName;
            if (file_put_contents($backupFolder . $fileName, $data) === false) {
                if (is_file($backupFile) === false) {
                    self::$logger->error('The backup file "' . $backupFile . '" could not be created');
                } else {
                    self::$logger->error('The backup file "' . $backupFile . '" was created but could not be written');
                }
            } else {
                self::$logger->notice(
                    'Successfully created uncompressed backup for "' . $tableName . '"',
                    array(
                        'fileName' => $backupFile,
                        'fileSize' => filesize($backupFile),
                    )
                );
            }
        }
    }

    /**
     * @return void
     */
    protected static function initializeLogger()
    {
        if (self::$logger === null) {
            self::$logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(
                get_called_class()
            );
        }
    }
}
