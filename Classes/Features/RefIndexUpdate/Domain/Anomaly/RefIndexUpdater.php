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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Features\RefIndexUpdate\Domain\Model\Task\RefIndexUpdateTask;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function is_int;

class RefIndexUpdater
{
    /** @var TaskRepository */
    protected $taskRepository;

    /** @var array<string, array<int, int>> */
    protected $configuration = [];

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function registerRefIndexUpdate(RecordInterface $record): void
    {
        $uid = $record->getIdentifier();

        // MM records and physical folders have string identifiers. They can not have a refIndex.
        if (is_int($uid)) {
            $table = $record->getTableName();
            $this->configuration[$table][$uid] = $uid;
        }
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public function writeRefIndexUpdateTask(): void
    {
        if (empty($this->configuration)) {
            return;
        }
        $config = $this->configuration;
        $this->configuration = [];
        $this->taskRepository->add(GeneralUtility::makeInstance(RefIndexUpdateTask::class, $config));
    }
}
