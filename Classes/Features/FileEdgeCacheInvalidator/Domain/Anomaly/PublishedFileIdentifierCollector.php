<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Anomaly;

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

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepositoryInjection;
use In2code\In2publishCore\Event\RecordWasPublished;
use In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Model\Task\FlushFileEdgeCacheTask;

use function array_keys;

class PublishedFileIdentifierCollector
{
    use TaskRepositoryInjection;

    /** @var array<true> */
    protected array $collectedRecords = [];

    public function registerPublishedFile(RecordWasPublished $event): void
    {
        $record = $event->getRecord();
        if ('sys_file' !== $record->getClassification()) {
            return;
        }
        $this->collectedRecords[$record->getId()] = true;
    }

    public function writeFlushFileEdgeCacheTask(): void
    {
        if (empty($this->collectedRecords)) {
            return;
        }
        $this->taskRepository->add(new FlushFileEdgeCacheTask(array_keys($this->collectedRecords)));
        $this->collectedRecords = [];
    }
}
