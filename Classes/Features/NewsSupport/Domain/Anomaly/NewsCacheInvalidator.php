<?php
namespace In2code\In2publishCore\Features\NewsSupport\Domain\Anomaly;

/***************************************************************
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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
 ***************************************************************/

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Features\NewsSupport\Domain\Model\Task\FlushNewsCacheTask;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class NewsCacheInvalidator
 */
class NewsCacheInvalidator implements SingletonInterface
{
    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * @var array
     */
    protected $newsCacheUidArray = [];

    /**
     * @var array
     */
    protected $newsCachePidArray = [];

    /**
     * Constructor
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
    }

    /**
     * @param string $tableName
     * @param RecordInterface $record
     * @return void
     */
    public function registerClearCacheTasks($tableName, RecordInterface $record)
    {
        $this->flushNewsCache($tableName, $record);
    }

    /**
     * Flush cache especially for tx_news
     *
     * @param string $tableName
     * @param RecordInterface $record
     * @return void
     */
    protected function flushNewsCache($tableName, RecordInterface $record)
    {
        if ($tableName === 'tx_news_domain_model_news' && $record->localRecordExists()) {
            $uid = $record->getLocalProperty('uid');
            if (!isset($this->newsCacheUidArray[$uid])) {
                $this->newsCacheUidArray[$uid] = 'tx_news_uid_' . $uid;
            }
            $pid = $record->getLocalProperty('pid');
            if (!isset($this->newsCachePidArray[$pid])) {
                $this->newsCachePidArray[$pid] = 'tx_news_pid_' . $pid;
            }
        }
    }

    /**
     *
     */
    public function writeClearCacheTask()
    {
        if (!empty($this->newsCacheUidArray)) {
            $flushNewsCacheTask = new FlushNewsCacheTask(['tagsToFlush' => $this->newsCacheUidArray]);
            $this->taskRepository->add($flushNewsCacheTask);
        }

        if (!empty($this->newsCachePidArray)) {
            $flushNewsCacheTask = new FlushNewsCacheTask(['tagsToFlush' => $this->newsCachePidArray]);
            $this->taskRepository->add($flushNewsCacheTask);
        }
    }
}
