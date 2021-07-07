<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\CacheInvalidation\Domain\Anomaly;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
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
use In2code\In2publishCore\Features\CacheInvalidation\Domain\Model\Task\FlushFrontendPageCacheTask;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;

use function array_filter;
use function array_key_exists;
use function implode;

class CacheInvalidator implements SingletonInterface
{
    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /** @var array<int, int> */
    protected $clearCachePids = [];

    /** @var array<int, null|string> */
    protected $clearCacheCommands = [];

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function registerClearCacheTasks(RecordInterface $record): void
    {
        if ($record->isPagesTable()) {
            $pid = (int)$record->getIdentifier();
        } elseif ($record->hasLocalProperty('pid')) {
            // Hint for the condition: only check the local PID,
            // because after publishing foreign and local PID will be the same.
            // If the merged or foreign PID is checked then a cache which might be wrong would get flushed.
            $pid = (int)$record->getLocalProperty('pid');
        } else {
            return;
        }

        $this->clearCachePids[$pid] = $pid;

        if (!array_key_exists($pid, $this->clearCacheCommands)) {
            $clearCacheCommand = null;
            $pageTsConfig = BackendUtility::getPagesTSconfig($pid);
            if (!empty($pageTsConfig['TCEMAIN.']['clearCacheCmd'])) {
                $clearCacheCommand = (string)$pageTsConfig['TCEMAIN.']['clearCacheCmd'];
            }
            $this->clearCacheCommands[$pid] = $clearCacheCommand;
        }
    }

    public function writeClearCacheTask(): void
    {
        $pids = $this->clearCachePids;
        $this->clearCachePids = [];

        if (!empty($pids)) {
            $flushPageCacheTask = new FlushFrontendPageCacheTask(['pid' => implode(',', $pids)]);
            $this->taskRepository->add($flushPageCacheTask);
        }

        $commands = array_filter($this->clearCacheCommands);
        $this->clearCacheCommands = [];

        if (!empty($commands)) {
            $clearCacheCommands = new FlushFrontendPageCacheTask(['pid' => implode(',', $commands)]);
            $this->taskRepository->add($clearCacheCommands);
        }
    }
}
