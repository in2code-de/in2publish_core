<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Features\NewsSupport\Domain\Model\Task;

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

use In2code\In2publishCore\Domain\Model\Task\AbstractTask;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FlushNewsCacheTask extends AbstractTask
{
    /**
     * Don't modify configuration
     *
     * @return void
     */
    public function modifyConfiguration()
    {
    }

    /**
     * Deletes all pages and news caches the same way they will be deleted on local
     *
     * @return bool
     *
     * @throws NoSuchCacheException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function executeTask(): bool
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        foreach ($this->configuration['tagsToFlush'] as $cacheTag) {
            $cacheManager->getCache('cache_pages')->flushByTag($cacheTag);
            $cacheManager->getCache('cache_pagesection')->flushByTag($cacheTag);
            $this->addMessage('Flushed all tx_news related caches for cache tag "' . $cacheTag . '"');
        }
        return true;
    }
}
