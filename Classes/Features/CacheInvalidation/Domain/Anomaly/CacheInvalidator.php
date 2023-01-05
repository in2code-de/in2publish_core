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

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepositoryInjection;
use In2code\In2publishCore\Event\RecordWasPublished;
use In2code\In2publishCore\Features\CacheInvalidation\Domain\Model\Task\FlushFrontendPageCacheTask;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;

use function array_filter;
use function array_key_exists;
use function array_unique;
use function implode;

class CacheInvalidator implements SingletonInterface
{
    use TaskRepositoryInjection;

    /** @var array<int, int> */
    protected array $clearCachePids = [];
    /** @var array<int, string|null> */
    protected array $clearCacheCommands = [];

    public function registerClearCacheTasks(RecordWasPublished $event): void
    {
        $pids = [];
        $record = $event->getRecord();
        if ('pages' === $record->getClassification()) {
            $pids[] = (int)$record->getId();
        } else {
            // Hint for the condition: check both PIDs,
            // because before publishing foreign and local PID could be different
            // and the page cache for the former parent page has to be cleared
            $localProps = $record->getLocalProps();
            if (array_key_exists('pid', $localProps)) {
                $pids[] = (int)$localProps['pid'];
            }
            $foreignProps = $record->getForeignProps();
            if (array_key_exists('pid', $foreignProps)) {
                $pids[] = (int)$foreignProps['pid'];
            }
        }

        foreach (array_unique($pids) as $pid) {
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
    }

    public function writeClearCacheTask(): void
    {
        if (!empty($this->clearCachePids)) {
            $this->taskRepository->add(new FlushFrontendPageCacheTask(['pid' => implode(',', $this->clearCachePids)]));
            $this->clearCachePids = [];
        }

        $commands = array_filter($this->clearCacheCommands);
        $this->clearCacheCommands = [];

        if (!empty($commands)) {
            $clearCacheCommands = new FlushFrontendPageCacheTask(['pid' => implode(',', $commands)]);
            $this->taskRepository->add($clearCacheCommands);
        }
    }
}
