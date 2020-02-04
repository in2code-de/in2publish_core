<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\PublishTaskRunner;

/*
 * Copyright notice
 *
 * (c) 2019 in2code.de and the following authors:
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

use In2code\In2publishCore\Domain\Model\Task\AbstractTask;
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Service\Context\ContextService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function json_encode;

class RunTasksInQueueCommand extends Command
{
    public const DESCRIPTION = <<<'TXT'
Reads all Tasks to execute from the Database and executes them one after another.
  The success of a Task is echoed to the console or scheduler backend module, including any error message of failed tasks.
  NOTE: This command is used for internal operations in in2publish_core
TXT;
    public const IDENTIFIER = 'in2publish_core:publishtasksrunner:runtasksinqueue';

    protected function configure()
    {
        $this->setHidden(true)
             ->setDescription(static::DESCRIPTION);
    }

    public function isEnabled()
    {
        return GeneralUtility::makeInstance(ContextService::class)->isForeign();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
        $result = [];
        // Tasks which should get executed do not have an execution begin
        $tasksToExecute = $taskRepository->findByExecutionBegin(null);
        /** @var AbstractTask $task */
        foreach ($tasksToExecute as $task) {
            try {
                $success = $task->execute();
                $result[] = 'Task ' . $task->getUid() . ($success ? ' was executed successfully' : ' failed');
                $result[] = $task->getMessages();
            } catch (Throwable $e) {
                $result[] = $e->getMessage();
            }
            $taskRepository->update($task);
        }
        if (empty($result)) {
            $result[] = 'There was nothing to execute';
        }
        $output->write(json_encode($result));
        return 0;
    }
}
