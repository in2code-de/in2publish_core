<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Features\RealUrlSupport\Domain\Model\Task;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Model\Task\AbstractTask;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Utility\EidUtility;

class RealUrlTask extends AbstractTask
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function modifyConfiguration()
    {
        if (!empty($this->configuration['record'])) {
            $record = $this->configuration['record'];
            if ($record instanceof RecordInterface) {
                $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
                $this->configuration = [
                    'identifier' => $record->getIdentifier(),
                    'requestFrontend' => $configContainer->get('tasks.realUrl.requestFrontend'),
                ];
            }
        }
    }

    /**
     * @return bool
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @throws ServiceUnavailableException
     */
    protected function executeTask(): bool
    {
        $this->connection = DatabaseUtility::buildLocalDatabaseConnection();
        $pid = $this->configuration['identifier'];
        $rootline = BackendUtility::BEgetRootLine($pid);
        $host = BackendUtility::firstDomainRecord($rootline);
        $_SERVER['HTTP_HOST'] = $host;
        /** @var CacheService $cacheService */
        $cacheService = GeneralUtility::makeInstance(CacheService::class);

        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = GeneralUtility::makeInstance(TimeTracker::class, false);
        }

        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $GLOBALS['TYPO3_CONF_VARS'],
            1,
            0,
            true
        );
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->id = 0;
        $GLOBALS['TSFE']->fe_user = EidUtility::initFeUser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();
        $GLOBALS['TSFE']->set_no_cache('Refresh RealUrl');
        $GLOBALS['TSFE']->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $uidArray = [];

        foreach ($rootline as $page) {
            $pageUid = (int)$page['uid'];
            $cacheService->clearPageCache($pageUid);
            $this->cleanAllRealUrlCachesForPageIdentifier($pageUid);
            $this->addMessage('Cleared realUrl cf_pages_cache entries for pid: ' . $pageUid);
            $uidArray[] = $pageUid;
        }

        foreach ($uidArray as $uid) {
            if ($uid !== 0) {
                $link = $GLOBALS['TSFE']->cObj->getTypoLink_URL($uid);
                $this->addMessage('Built typolink for pid: ' . $uid);
                if ($this->configuration['requestFrontend']) {
                    $url = 'http://' . $host . '/' . ltrim($link, '/');
                    $result = GeneralUtility::getUrl($url);
                    $this->addMessage(
                        'requested page: "' . $url . '" Request ' .
                        ($result !== false ? 'was successful' : 'failed')
                    );
                }
            }
        }

        return true;
    }

    /**
     * Clean all RealUrl cache tables for a defined pageUid
     *
     * @param int $pageUid
     * @return void
     */
    protected function cleanAllRealUrlCachesForPageIdentifier(int $pageUid)
    {
        $realUrlTables = [
            // realurl < 2.2
            'tx_realurl_pathcache',
            // realurl 1.x
            'tx_realurl_urlencodecache',
            // realurl 1.x
            'tx_realurl_urldecodecache',
            // realurl < 2.2
            'tx_realurl_urlcache',
        ];
        foreach ($realUrlTables as $table) {
            if (DatabaseUtility::isTableExistingOnLocal($table)) {
                $this->connection->delete($table, ['page_id' => $pageUid]);
            }
        }
    }
}
