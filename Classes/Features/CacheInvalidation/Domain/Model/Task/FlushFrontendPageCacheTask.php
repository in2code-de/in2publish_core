<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\CacheInvalidation\Domain\Model\Task;

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

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task\AbstractTask;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use function sprintf;

class FlushFrontendPageCacheTask extends AbstractTask
{
    /**
     * Flushes the frontend caches of the published pages.
     *
     * For historical reasons the configuration key is called "pid", but it holds a comma separated
     * list of cache clear commands. Each command is either a page uid or one of the keywords
     * supported by DataHandler::clear_cacheCmd().
     *
     * Examples:
     *   ['pid' => '13']
     *   ['pid' => '1,2,3']
     *   ['pid' => 'all']
     *   ['pid' => 'pages']
     *   ['pid' => 'cacheTag:pagetag1']
     */
    protected function executeTask(): bool
    {
        $cacheCommands = GeneralUtility::trimExplode(',', (string)($this->configuration['pid'] ?? ''), true);
        if ([] === $cacheCommands) {
            $this->addMessage('Skipped flushing the frontend cache because the task configuration contains no PID');
            return false;
        }

        $dataHandler = $this->getDataHandler();
        $pageIds = [];

        foreach ($cacheCommands as $cacheCommand) {
            if (MathUtility::canBeInterpretedAsInteger($cacheCommand)) {
                $pageIds[] = (int)$cacheCommand;
            } else {
                $dataHandler->clear_cacheCmd($cacheCommand);
            }
            $this->addMessage(sprintf('Cleared frontend cache with configuration clearCacheCmd=%s', $cacheCommand));
        }

        $this->flushCachesForPages($dataHandler, $pageIds);

        return true;
    }

    /**
     * Flushes the caches of the given pages the same way the core does when a page is changed in
     * the backend. This also covers the caches of the sibling and the parent pages, so menus and
     * sitemaps referencing a published page are rendered anew.
     *
     * @param array<int> $pageIds
     */
    protected function flushCachesForPages(DataHandler $dataHandler, array $pageIds): void
    {
        if ([] === $pageIds) {
            return;
        }

        foreach ($pageIds as $pageId) {
            $dataHandler->registerRecordIdForPageCacheClearing('pages', $pageId);
        }

        // The registered pages are flushed in DataHandler::processClearCacheQueue(), which is
        // protected and therefore has to be triggered indirectly.
        $dataHandler->process_cmdmap();
    }

    protected function getDataHandler(): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        /** @var CommandLineUserAuthentication $user */
        $user = $GLOBALS['BE_USER'];

        if (!$user->user) {
            $user->authenticate();
        }

        // start() initializes the ReferenceIndexUpdater, which is required by process_cmdmap().
        $dataHandler->start([], [], $user);

        // Must be set after start(), because start() overrules it with the backend user's admin flag.
        /** @psalm-suppress InternalProperty */
        $dataHandler->admin = true;

        return $dataHandler;
    }
}
