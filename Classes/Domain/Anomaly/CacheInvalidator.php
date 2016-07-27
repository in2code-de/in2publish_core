<?php
namespace In2code\In2publishCore\Domain\Anomaly;

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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\Task\FlushFrontendPageCacheTask;
use In2code\In2publishCore\Domain\Model\Task\FlushNewsCacheTask;
use In2code\In2publishCore\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PhysicalFilePublisher
 *
 * @package In2code\In2publish\Domain\Model\Anomaly
 */
class CacheInvalidator implements SingletonInterface
{
    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var \In2code\In2publishCore\Domain\Repository\TaskRepository
     * @inject
     */
    protected $taskRepository;

    /**
     * @var array
     */
    protected $clearCachePids = array();

    /**
     * @var array
     */
    protected $clearCacheCommands = array();

    /**
     * @var array
     */
    protected $newsCacheUidsArray = array();

    /**
     * @var array
     */
    protected $newsCachePidsArray = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
    }

    /**
     * @param string $tableName
     * @param Record $record
     * @return void
     */
    public function registerClearCacheTasks($tableName, Record $record)
    {
        $this->flushPageCache($tableName, $record);
        $this->flushPageCacheByClearCacheComand($tableName, $record);
        $this->flushNewsCache($tableName, $record);
    }

    /**
     *
     */
    public function writeClearCacheTask()
    {
        if (!empty($this->clearCachePids)) {
            $flushPageCacheTask = new FlushFrontendPageCacheTask(array('pid' => implode(',', $this->clearCachePids)));
            $this->taskRepository->add($flushPageCacheTask);
        }

        $this->clearCacheCommands = array_filter($this->clearCacheCommands);

        if (!empty($this->clearCacheCommands)) {
            $clearCacheCommands = new FlushFrontendPageCacheTask(
                array('pid' => implode(',', $this->clearCacheCommands))
            );
            $this->taskRepository->add($clearCacheCommands);
        }

        if (!empty($this->newsCachePidsArray)) {
            $flushNewsCacheTask = new FlushNewsCacheTask(array('tagsToFlush' => $this->newsCachePidsArray));
            $this->taskRepository->add($flushNewsCacheTask);
        }

        if (!empty($this->newsCacheUidsArray)) {
            $flushNewsCacheTask = new FlushNewsCacheTask(array('tagsToFlush' => $this->newsCacheUidsArray));
            $this->taskRepository->add($flushNewsCacheTask);
        }
    }

    /**
     * Flush cache by given page identifier
     *
     * @param string $tableName
     * @param Record $record
     * @return void
     */
    protected function flushPageCache($tableName, Record $record)
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
     * @return void
     */
    protected function flushPageCacheByClearCacheComand($tableName, Record $record)
    {
        if ($tableName === 'pages') {
            $pid = (int)$record->getIdentifier();
        } elseif ($record->hasLocalProperty('pid')) {
            // \In2code\In2publishCore\Domain\Anomaly\CacheInvalidator::flushPageCache for info about the above condition
            $pid = (int)$record->getLocalProperty('pid');
        } else {
            $pid = null;
        }

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
     * Flush cache especially for tx_news
     *
     * @param string $tableName
     * @param Record $record
     * @return void
     */
    protected function flushNewsCache($tableName, Record $record)
    {
        if ($tableName === 'tx_news_domain_model_news' && $record->localRecordExists()) {
            $uid = $record->getLocalProperty('uid');
            if (!isset($this->newsCacheUidsArray[$uid])) {
                $this->newsCacheUidsArray[$uid] = 'tx_news_uid_' . $uid;
            }
            $pid = $record->getLocalProperty('pid');
            if (!isset($this->newsCachePidsArray[$pid])) {
                $this->newsCachePidsArray[$pid] = 'tx_news_pid_' . $pid;
            }
        }
    }
}
