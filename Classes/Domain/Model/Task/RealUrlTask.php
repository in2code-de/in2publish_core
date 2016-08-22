<?php
namespace In2code\In2publishCore\Domain\Model\Task;

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
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Frontend\Utility\EidUtility;

/**
 * Class RealUrlTask
 */
class RealUrlTask extends AbstractTask
{
    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * @return void
     */
    public function modifyConfiguration()
    {
        if (!empty($this->configuration['record'])) {
            $record = $this->configuration['record'];
            if ($record instanceof Record) {
                $this->configuration = array(
                    'identifier' => $record->getIdentifier(),
                    'requestFrontend' => ConfigurationUtility::getConfiguration('tasks.realUrl.requestFrontend'),
                );
            }
        }
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function executeTask()
    {
        $this->databaseConnection = DatabaseUtility::buildLocalDatabaseConnection();
        $pid = $this->configuration['identifier'];
        $rootline = BackendUtility::BEgetRootLine($pid);
        $host = BackendUtility::firstDomainRecord($rootline);
        $_SERVER['HTTP_HOST'] = $host;
        /** @var CacheService $cacheService */
        $cacheService = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\CacheService');

        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TimeTracker\\NullTimeTracker');
        }

        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController',
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
        $GLOBALS['TSFE']->cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');

        $uidArray = array();

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
    protected function cleanAllRealUrlCachesForPageIdentifier($pageUid)
    {
        $realUrlTables = array(
            'tx_realurl_pathcache', // realurl 1.x and 2.x
            'tx_realurl_urlencodecache', // realurl 1.x
            'tx_realurl_urldecodecache', // realurl 1.x
            'tx_realurl_urlcache' // realurl 2.x
        );
        foreach ($realUrlTables as $table) {
            if (DatabaseUtility::isTableExistingOnLocal($table)) {
                $this->databaseConnection->exec_DELETEquery($table, 'page_id=' . (int)$pageUid);
            }
        }
    }
}
