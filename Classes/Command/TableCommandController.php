<?php
namespace In2code\In2publishCore\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
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

use In2code\In2publishCore\Security\SshConnection;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class TableCommandController (enabled on local and foreign)
 */
class TableCommandController extends AbstractCommandController
{
    const PUBLISH_COMMAND = 'table:publish --table-name=%s';
    const IMPORT_COMMAND = 'table:import --table-name=%s';
    const BACKUP_COMMAND = 'table:backup --table-name=%s';

    /**
     * @var DatabaseConnection
     */
    protected $localDatabase = null;

    /**
     * @var DatabaseConnection
     */
    protected $foreignDatabase = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
    }

    /**
     * Copies a table from Stage to Live
     *
     *      Copies a complete table from stage to live and overwrites all old entries!
     *
     * @param string $tableName
     * @return void
     */
    public function publishCommand($tableName)
    {
        if (!$this->contextService->isLocal()) {
            $this->outputLine('This command is available on Local only');
            $this->sendAndExit(4);
        }
        $this->logger->notice('Called Publish Table Command for table name "' . $tableName . '"');
        $backupResults = SshConnection::makeInstance()->backupRemoteTable($tableName);
        $this->logger->info('Backup results from foreign system:', array('backupResults' => $backupResults));
        if ($this->copyTableContents($this->localDatabase, $this->foreignDatabase, $tableName)) {
            $this->logger->notice('Finished publishing of table "' . $tableName . '"');
        } else {
            $this->logger->critical('Could not truncate foreign table "' . $tableName . '". Skipping import');
        }
    }

    /**
     * Copies a table from Live to Stage
     *
     *      Copies a complete table from live to stage and overwrites all old entries!
     *
     * @param string $tableName
     * @return void
     */
    public function importCommand($tableName)
    {
        if (!$this->contextService->isLocal()) {
            $this->outputLine('This command is available on Local only');
            $this->sendAndExit(4);
        }
        $this->logger->notice('Called Import Table Command for table "' . $tableName . '"');
        DatabaseUtility::backupTable($this->localDatabase, $tableName);
        if ($this->copyTableContents($this->foreignDatabase, $this->localDatabase, $tableName)) {
            $this->logger->notice('Finished importing of table "' . $tableName . '"');
        } else {
            $this->logger->critical('Could not truncate local table "' . $tableName . '". Skipping import');
        }
    }

    /**
     * @param DatabaseConnection $fromDatabase
     * @param DatabaseConnection $toDatabase
     * @param string $tableName
     * @return bool
     */
    protected function copyTableContents(DatabaseConnection $fromDatabase, DatabaseConnection $toDatabase, $tableName)
    {
        if ($this->truncateTable($toDatabase, $tableName)) {
            $queryResult = $fromDatabase->exec_SELECTquery('*', $tableName, '1=1');
            $this->logger->notice('Successfully truncated table, importing ' . $queryResult->num_rows . ' rows');
            while (($row = $fromDatabase->sql_fetch_assoc($queryResult))) {
                if (($success = $this->insertRow($toDatabase, $tableName, $row)) !== true) {
                    $this->logger->critical(
                        'Failed to import row into "' . $tableName . '"',
                        array(
                            'row' => $row,
                            'result' => $success,
                        )
                    );
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Returns TRUE on success or FALSE on failure
     *
     * @param DatabaseConnection $databaseConnection
     * @param string $tableName
     * @param array $row
     * @return bool
     */
    protected function insertRow(DatabaseConnection $databaseConnection, $tableName, array $row)
    {
        return $databaseConnection->exec_INSERTquery($tableName, $row);
    }

    /**
     * Returns TRUE on success or FALSE on failure
     *
     * @param DatabaseConnection $databaseConnection
     * @param $tableName
     * @return bool
     */
    protected function truncateTable(DatabaseConnection $databaseConnection, $tableName)
    {
        return $databaseConnection->exec_TRUNCATEquery($tableName);
    }

    /**
     * Stores a backup of a complete table into the configured directory
     *
     * @param string $tableName
     * @return void
     */
    public function backupCommand($tableName)
    {
        $this->logger->notice('Called Backup Table Command for table "' . $tableName . '"');
        DatabaseUtility::backupTable($this->localDatabase, $tableName);
    }
}
