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

class FlushFrontendPageCacheTask extends AbstractTask
{
    /**
     * Flush Frontend Caches of given pages
     *        expected:
     *        $this->configuration = array(
     *            'pid' => '1,2,3'
     *        )
     *
     *        Possible values:
     *        'pid' => '13'
     *        'pid' => '1,2,3'
     *        'pid' => 'all'
     *        'pid' => 'pages'
     *        'pid' => 'cacheTag:pagetag1'
     */
    protected function executeTask(): bool
    {
        $dataHandler = $this->getDataHandler();
        $commands = GeneralUtility::trimExplode(',', $this->configuration['pid'], true);
        foreach ($commands as $command) {
            $dataHandler->clear_cacheCmd($command);
            $this->addMessage('Cleared frontend cache with configuration clearCacheCmd=' . $command);
        }
        return true;
    }

    protected function getDataHandler(): DataHandler
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $beUser = $GLOBALS['BE_USER'];
        if (!isset($beUser->user['username']) && $beUser instanceof CommandLineUserAuthentication) {
            /** @psalm-suppress InternalProperty */
            $beUser->user['username'] = '_cli_';
        }
        $dataHandler->BE_USER = $beUser;
        /** @psalm-suppress InternalProperty */
        $dataHandler->admin = true;
        return $dataHandler;
    }
}
