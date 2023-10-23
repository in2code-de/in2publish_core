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

use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepositoryInjection;
use In2code\In2publishCore\Event\RecordWasPublished;
use In2code\In2publishCore\Features\NewsSupport\Domain\Model\Task\FlushNewsCacheTask;

use function array_keys;

class NewsCacheInvalidator
{
    use TaskRepositoryInjection;

    /** @var array<int, int> */
    protected array $newsCacheUidArray = [];
    /** @var array<int, int> */
    protected array $newsCachePidArray = [];

    public function registerClearCacheTasks(RecordWasPublished $event): void
    {
        $record = $event->getRecord();
        if (
            'tx_news_domain_model_news' !== $record->getClassification()
            || Record::S_DELETED === $record->getState()
        ) {
            return;
        }

        $localProps = $record->getLocalProps();

        $uid = $localProps['uid'];
        $this->newsCacheUidArray[$uid] = true;

        $pid = $localProps['pid'];
        $this->newsCachePidArray[$pid] = true;
    }

    public function writeClearCacheTask(): void
    {
        if (empty($this->newsCacheUidArray)) {
            return;
        }

        $this->taskRepository->add(
            new FlushNewsCacheTask(
                [
                    'uid' => array_keys($this->newsCacheUidArray),
                    'pid' => array_keys($this->newsCachePidArray),
                ],
            ),
        );
        $this->newsCacheUidArray = [];
        $this->newsCachePidArray = [];
    }
}
