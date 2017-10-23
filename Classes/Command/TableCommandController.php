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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Service\Database\DatabaseSchemaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TableCommandController (enabled on local and foreign)
 */
class TableCommandController extends AbstractCommandController
{
    const PUBLISH_COMMAND = 'table:publish --table-name=%s';
    const IMPORT_COMMAND = 'table:import --table-name=%s';
    const BACKUP_COMMAND = 'table:backup --table-name=%s';
    const EXIT_INVALID_TABLE = 220;

    /**
     * @var DatabaseConnection
     */
    protected $localDatabase = null;

    /**
     * @var DatabaseConnection
     */
    protected $foreignDatabase = null;

    /**
     * @var DatabaseSchemaService
     */
    protected $dbSchemaService = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        if ($this->contextService->isLocal()) {
            $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        }
        $this->dbSchemaService = GeneralUtility::makeInstance(DatabaseSchemaService::class);
    }

    /**
     * Copies a table from stage to production
     *
     *      Copies a complete table from stage to production and overwrites all old entries!
     *
     * @param string $tableName
     * @return void
     */
    public function publishCommand($tableName)
    {
        $this->checkLocalContext();
        $this->checkTableExists($tableName);

        $this->logger->notice('Called Publish Table Command for table name "' . $tableName . '"');

        $request = GeneralUtility::makeInstance(
            RemoteCommandRequest::class,
            'table:backup',
            ['--table-name' => $tableName]
        );
        $response = GeneralUtility::makeInstance(RemoteCommandDispatcher::class)->dispatch($request);

        if ($response->isSuccessful()) {
            $this->logger->info('Backup seems to be successful.');

            if ($this->copyTableContents($this->localDatabase, $this->foreignDatabase, $tableName)) {
                $this->logger->notice('Finished publishing of table "' . $tableName . '"');
            } else {
                $this->logger->critical('Could not truncate foreign table "' . $tableName . '". Skipping import');
            }
        } else {
            $this->logger->error(
                'Could not create backup on remote:',
                [
                    'errors' => $response->getErrors(),
                    'exit_status' => $response->getExitStatus(),
                    'output' => $response->getOutput(),
                ]
            );
        }
    }

    /**
     * Copies a table from production to stage
     *
     *      Copies a complete table from production to stage and overwrites all old entries!
     *
     * @param string $tableName
     * @return void
     */
    public function importCommand($tableName)
    {
        $this->checkLocalContext();
        $this->checkTableExists($tableName);

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
                        [
                            'row' => $row,
                            'result' => $success,
                        ]
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
     * Stores a backup of the complete local table into the configured directory
     *
     * @param string $tableName
     * @return void
     */
    public function backupCommand($tableName)
    {
        $this->logger->notice('Called Backup Table Command for table "' . $tableName . '"');
        DatabaseUtility::backupTable($this->localDatabase, $tableName);
    }

    protected function checkLocalContext()
    {
        if (!$this->contextService->isLocal()) {
            $this->outputLine('This command is available on Local only');
            $this->sendAndExit(static::EXIT_WRONG_CONTEXT);
        }
    }

    /**
     * @param $tableName
     */
    protected function checkTableExists($tableName)
    {
        if (!$this->dbSchemaService->tableExists($tableName)) {
            $this->outputLine('The table does not exist');
            $this->sendAndExit(static::EXIT_INVALID_TABLE);
        }
    }
}
