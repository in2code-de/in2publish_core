<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RefIndexUpdate\Domain\Anomaly;

/*
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
 * Holger Krämer <post@holgerkraemer.com>
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

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Features\RefIndexUpdate\Domain\Model\Task\RefIndexUpdateTask;

use function is_int;

class RefIndexUpdater
{
    protected TaskRepository $taskRepository;
    /** @var array<string, array<int, int>> */
    protected array $configuration = [];

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function registerRefIndexUpdate(PublishingOfOneRecordEnded $event): void
    {
        $record = $event->getRecord();
        $uid = $record->getId();

        // MM records and physical folders have string identifiers. They can not have a refIndex.
        if (is_int($uid)) {
            $table = $record->getClassification();
            $this->configuration[$table][$uid] = $uid;
        }
    }

    public function writeRefIndexUpdateTask(): void
    {
        if (empty($this->configuration)) {
            return;
        }
        $this->taskRepository->add(new RefIndexUpdateTask($this->configuration));
        $this->configuration = [];
    }
}
