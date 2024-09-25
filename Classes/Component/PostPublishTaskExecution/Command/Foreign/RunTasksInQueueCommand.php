<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\PostPublishTaskExecution\Command\Foreign;

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

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task\AbstractTask;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepositoryInjection;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function get_class;
use function implode;
use function json_encode;
use function max;
use function sprintf;

use const JSON_THROW_ON_ERROR;

class RunTasksInQueueCommand extends Command implements LoggerAwareInterface
{
    use ContextServiceInjection;
    use TaskRepositoryInjection;
    use LoggerAwareTrait;

    public const IDENTIFIER = 'in2publish_core:publishtasksrunner:runtasksinqueue';

    public function isEnabled(): bool
    {
        return $this->contextService->isForeign();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = Command::SUCCESS;
        $result = [];
        // Tasks which should get executed do not have an execution begin
        $tasksToExecute = $this->taskRepository->findByExecutionBegin();
        foreach ($tasksToExecute as $task) {
            $exitCode = max($exitCode, $this->runTask($task, $result));
            $this->taskRepository->update($task);
        }
        if (empty($result)) {
            $result[] = 'There was nothing to execute';
        }
        $output->write(json_encode($result, JSON_THROW_ON_ERROR));
        return $exitCode;
    }

    protected function runTask(AbstractTask $task, array &$result): int
    {
        $taskUid = $task->getUid();

        try {
            $success = $task->execute();
            $messages = $task->getMessages();
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Task %d (%s) failed with exception: "%s".',
                    $taskUid,
                    get_class($task),
                    $exception->getMessage(),
                ),
                ['exception' => $exception, 'task' => $task->toArray()],
            );
            $result[] = sprintf(
                'Task %d (%s) failed with exception: "%s". The exception was logged on the foreign system.',
                $taskUid,
                get_class($task),
                $exception->getMessage(),
            );
            return Command::FAILURE;
        }

        if (!$success) {
            $result[] = sprintf(
                'Task %d (%s) failed with messages: "%s".',
                $taskUid,
                get_class($task),
                implode(', ', $messages),
            );
            return Command::FAILURE;
        }

        $result[] = sprintf(
            'Task %d (%s) executed with messages: "%s".',
            $taskUid,
            get_class($task),
            implode(', ', $messages),
        );
        return Command::SUCCESS;
    }
}
