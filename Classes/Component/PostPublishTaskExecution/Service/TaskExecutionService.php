<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\PostPublishTaskExecution\Service;

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

use In2code\In2publishCore\CommonInjection\EventDispatcherInjection;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Command\Foreign\RunTasksInQueueCommand;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\Exception\TaskExecutionFailedException;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Event\TaskExecutionWasFinished;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class TaskExecutionService implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use EventDispatcherInjection;

    protected RemoteCommandDispatcher $remoteCommandDispatcher;
    protected TaskRepository $taskRepository;

    public function injectRemoteCommandDispatcher(RemoteCommandDispatcher $remoteCommandDispatcher): void
    {
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }

    public function injectTaskRepository(TaskRepository $taskRepository): void
    {
        $this->taskRepository = $taskRepository;
    }

    public function runTasks(): void
    {
        $request = new RemoteCommandRequest(RunTasksInQueueCommand::IDENTIFIER);
        $response = $this->remoteCommandDispatcher->dispatch($request);

        $this->taskRepository->deleteObsolete();

        $this->eventDispatcher->dispatch(new TaskExecutionWasFinished($response));

        if (!$response->isSuccessful()) {
            $this->logger->error(
                'Task execution failed',
                [
                    'output' => $response->getOutput(),
                    'errors' => $response->getErrors(),
                    'exit_status' => $response->getExitStatus(),
                ]
            );
            throw new TaskExecutionFailedException($response);
        }
        $this->logger->info('Task execution results', ['output' => $response->getOutput()]);
    }
}
