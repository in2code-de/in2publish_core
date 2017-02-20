<?php
namespace In2code\In2publishCore\Domain\Repository;

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

use In2code\In2publishCore\Domain\Model\Task\AbstractTask;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TaskRepository
 */
class TaskRepository
{
    const TASK_TABLE_NAME = 'tx_in2code_in2publish_task';

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * @var \In2code\In2publishCore\Domain\Factory\TaskFactory
     * @inject
     */
    protected $taskFactory = null;

    /**
     * @var ContextService
     */
    protected $contextService = null;

    /**
     * TaskRepository constructor.
     */
    public function __construct()
    {
        $this->contextService = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Service\\Context\\ContextService'
        );
        if ($this->contextService->isForeign()) {
            $this->databaseConnection = DatabaseUtility::buildLocalDatabaseConnection();
        } elseif ($this->contextService->isLocal()) {
            $this->databaseConnection = DatabaseUtility::buildForeignDatabaseConnection();
        }
        $now = new \DateTime('now');
        $this->creationDate = $now->format('Y-m-d H:i:s');
    }

    /**
     * Add a new Task to the queue
     *
     * @param AbstractTask $task
     * @return void
     */
    public function add(AbstractTask $task)
    {
        $this->databaseConnection->exec_INSERTquery(
            self::TASK_TABLE_NAME,
            array_merge($this->taskToPropertiesArray($task), array('creation_date' => $this->creationDate))
        );
    }

    /**
     * Update a Task to set execution time
     *
     * @param AbstractTask $task
     * @return void
     */
    public function update(AbstractTask $task)
    {
        $this->databaseConnection->exec_UPDATEquery(
            self::TASK_TABLE_NAME,
            'uid=' . $task->getUid(),
            $this->taskToPropertiesArray($task)
        );
    }

    /**
     * TODO: use __toArray in AbstractTask instead
     *
     * @param AbstractTask $task
     * @return array
     */
    protected function taskToPropertiesArray(AbstractTask $task)
    {
        $task->modifyConfiguration();
        return array(
            'task_type' => get_class($task),
            'configuration' => json_encode($task->getConfiguration()),
            'execution_begin' => $task->getExecutionBeginForPersistence(),
            'execution_end' => $task->getExecutionEndForPersistence(),
            'messages' => json_encode($task->getMessages()),
        );
    }

    /**
     * NULL: finds all Tasks which were not executed
     * DateTime: finds all Tasks which were executed on the given Time
     *
     * @param \DateTime $executionBegin
     * @return array|NULL
     */
    public function findByExecutionBegin(\DateTime $executionBegin = null)
    {
        if ($executionBegin instanceof \DateTime) {
            $whereClause = 'execution_begin=' . $executionBegin->format('Y-m-d H:i:s');
        } else {
            $whereClause = '(execution_begin IS NULL OR execution_begin LIKE "0000-00-00 00:00:00")';
        }
        $taskObjects = array();
        $tasksPropertiesArray = (array)$this->databaseConnection->exec_SELECTgetRows(
            '*',
            self::TASK_TABLE_NAME,
            $whereClause
        );
        foreach ($tasksPropertiesArray as $taskProperties) {
            $taskObjects[] = $this->taskFactory->convertToObject($taskProperties);
        }
        return $taskObjects;
    }
}
