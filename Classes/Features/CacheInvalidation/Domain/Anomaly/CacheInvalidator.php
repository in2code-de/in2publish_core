<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Features\CacheInvalidation\Domain\Anomaly;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de
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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Features\CacheInvalidation\Domain\Model\Task\FlushFrontendPageCacheTask;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_filter;
use function implode;

/**
 * Class PhysicalFilePublisher
 */
class CacheInvalidator implements SingletonInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * @var array
     */
    protected $clearCachePids = [];

    /**
     * @var array
     */
    protected $clearCacheCommands = [];

    /**
     * Constructor
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
    }

    /**
     * @param string $tableName
     * @param Record $record
     *
     * @return void
     */
    public function registerClearCacheTasks($tableName, Record $record)
    {
        $this->flushPageCache($tableName, $record);
        $this->flushPageCacheByClearCacheCommand($tableName, $record);
    }

    /**
     *
     */
    public function writeClearCacheTask()
    {
        if (!empty($this->clearCachePids)) {
            $flushPageCacheTask = new FlushFrontendPageCacheTask(['pid' => implode(',', $this->clearCachePids)]);
            $this->taskRepository->add($flushPageCacheTask);
        }

        $this->clearCacheCommands = array_filter($this->clearCacheCommands);

        if (!empty($this->clearCacheCommands)) {
            $clearCacheCommands = new FlushFrontendPageCacheTask(
                ['pid' => implode(',', $this->clearCacheCommands)]
            );
            $this->taskRepository->add($clearCacheCommands);
        }

        $this->clearCacheCommands = [];
        $this->clearCachePids = [];
    }

    /**
     * Flush cache by given page identifier
     *
     * @param string $tableName
     * @param Record $record
     *
     * @return void
     */
    protected function flushPageCache($tableName, Record $record)
    {
        $pid = $this->determinePid($tableName, $record);

        if (null !== $pid) {
            if (!isset($this->clearCachePids[$pid])) {
                $this->clearCachePids[$pid] = $pid;
            }
        }
    }

    /**
     * Flush cache by given TCEMAIN.clearCacheCmd entry
     *
     * @param string $tableName
     * @param Record $record
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function flushPageCacheByClearCacheCommand($tableName, Record $record)
    {
        $pid = $this->determinePid($tableName, $record);

        if (null !== $pid) {
            if (!isset($this->clearCacheCommands[$pid])) {
                $pageTsConfig = BackendUtility::getPagesTSconfig($pid);
                if (!empty($pageTsConfig['TCEMAIN.']['clearCacheCmd'])) {
                    $clearCacheCommand = (string)$pageTsConfig['TCEMAIN.']['clearCacheCmd'];
                } else {
                    // do not use "null" because of isset check
                    $clearCacheCommand = false;
                }
                $this->clearCacheCommands[$pid] = $clearCacheCommand;
            }
        }
    }

    /**
     * @param $tableName
     * @param Record $record
     *
     * @return int|null
     */
    protected function determinePid($tableName, Record $record)
    {
        if ($tableName === 'pages') {
            $pid = (int)$record->getIdentifier();
        } elseif ($record->hasLocalProperty('pid')) {
            // hint for the condition: check the local PID,
            // because after publishing foreign and local PID will be the same.
            // if the merged or foreign PID is checked then possibly wrong caches will get deleted
            $pid = (int)$record->getLocalProperty('pid');
        } else {
            $pid = null;
        }
        return $pid;
    }
}
