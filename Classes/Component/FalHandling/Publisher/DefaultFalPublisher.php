<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\FalHandling\Publisher;

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

use In2code\In2publishCore\Component\FalHandling\FalPublisher;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\TaskExecutionService;
use In2code\In2publishCore\Component\RecordHandling\RecordPublisher;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Service\Publishing\FolderPublisherService;

class DefaultFalPublisher implements FalPublisher
{
    private RecordPublisher $recordPublisher;

    private TaskExecutionService $taskExecutionService;

    private FolderPublisherService $folderPublisherService;

    public function __construct(
        RecordPublisher $recordPublisher,
        TaskExecutionService $taskExecutionService,
        FolderPublisherService $folderPublisherService
    ) {
        $this->recordPublisher = $recordPublisher;
        $this->taskExecutionService = $taskExecutionService;
        $this->folderPublisherService = $folderPublisherService;
    }

    public function publishFile(RecordInterface $record): void
    {
        try {
            $this->recordPublisher->publishRecordRecursive($record);
        } finally {
            $this->taskExecutionService->runTasks();
        }
    }

    public function publishFolder(string $identifier): void
    {
        try {
            $this->folderPublisherService->publish($identifier);
        } finally {
            $this->taskExecutionService->runTasks();
        }
    }
}
