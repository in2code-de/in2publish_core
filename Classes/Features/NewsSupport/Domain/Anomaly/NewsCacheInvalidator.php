<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\NewsSupport\Domain\Anomaly;

/*
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
 */

use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Features\NewsSupport\Domain\Model\Task\FlushNewsCacheTask;

class NewsCacheInvalidator
{
    /** @var TaskRepository */
    protected $taskRepository;

    /** @var array<int, string> */
    protected $newsCacheUidArray = [];

    /** @var array<int, string> */
    protected $newsCachePidArray = [];

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function registerClearCacheTasks(PublishingOfOneRecordBegan $event): void
    {
        $record = $event->getRecord();
        if ('tx_news_domain_model_news' !== $record->getTableName() || !$record->localRecordExists()) {
            return;
        }

        $uid = $record->getLocalProperty('uid');
        $this->newsCacheUidArray[$uid] = 'tx_news_uid_' . $uid;

        $pid = $record->getLocalProperty('pid');
        $this->newsCachePidArray[$pid] = 'tx_news_pid_' . $pid;
    }

    public function writeClearCacheTask(): void
    {
        if (empty($this->newsCacheUidArray)) {
            return;
        }

        $this->taskRepository->add(new FlushNewsCacheTask(['tagsToFlush' => $this->newsCacheUidArray]));
        $this->newsCacheUidArray = [];

        $this->taskRepository->add(new FlushNewsCacheTask(['tagsToFlush' => $this->newsCachePidArray]));
        $this->newsCachePidArray = [];
    }
}
