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

use In2code\In2publishCore\Domain\Model\Task\AbstractTask;

/**
 * Class PublishTasksRunnerCommandController (enabled on foreign)
 */
class PublishTasksRunnerCommandController extends AbstractCommandController
{
    const RUN_TASKS_COMMAND = 'publishtasksrunner:runtasksinqueue';

    /**
     * @var \In2code\In2publishCore\Domain\Repository\TaskRepository
     * @inject
     */
    protected $taskRepository;

    /**
     * Reads all Tasks to execute from the Database and executes them
     * one after another.
     * The success of a Task is outputted to the console or scheduler
     * backend module, including any error message of failed tasks
     * NOTE: This command is used for internal operations in in2publish
     *
     *
     * @return void
     */
    public function runTasksInQueueCommand()
    {
        $result = array();
        // Tasks which should get executed do not have an execution begin
        $tasksToExecute = $this->taskRepository->findByExecutionBegin(null);
        /** @var AbstractTask $task */
        foreach ($tasksToExecute as $task) {
            try {
                if ($task->execute()) {
                    $result[] = 'Task ' . $task->getUid() . ' was executed successfully';
                } else {
                    $result[] = 'Task ' . $task->getUid() . ' failed';
                }
                $result[] = $task->getMessages();
            } catch (\Exception $e) {
                $result[] = $e->getMessage();
            }
            $this->taskRepository->update($task);
        }
        if (empty($result)) {
            $result[] = 'There was nothing to execute';
        }
        echo json_encode($result);
    }
}
