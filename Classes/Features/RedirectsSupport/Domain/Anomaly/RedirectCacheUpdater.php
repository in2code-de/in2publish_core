<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Anomaly;

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

use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\Task\RebuildRedirectCacheTask;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RedirectCacheUpdater implements SingletonInterface
{
    protected $redirectWasPublished = false;

    public function publishRecordRecursiveAfterPublishing(string $tableName): void
    {
        if ('sys_redirect' !== $tableName) {
            return;
        }
        $this->redirectWasPublished = true;
    }

    public function publishRecordRecursiveEnd()
    {
        if ($this->redirectWasPublished) {
            $task = GeneralUtility::makeInstance(RebuildRedirectCacheTask::class, []);
            GeneralUtility::makeInstance(TaskRepository::class)->add($task);
        }
    }
}
