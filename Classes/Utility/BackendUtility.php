<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Utility;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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

use Closure;
use In2code\In2publishCore\Domain\Service\DomainService;
use In2code\In2publishCore\Domain\Service\Exception\PageDoesNotExistException;
use In2code\In2publishCore\Domain\Service\ForeignSiteFinder;
use In2code\In2publishCore\In2publishCoreException;
use PDO;
use Throwable;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use function array_key_exists;
use function array_keys;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function key;
use function md5;
use function parse_str;
use function parse_url;
use function rtrim;
use function stristr;
use function strpos;
use function strtolower;
use function trigger_error;
use function version_compare;

use const E_USER_DEPRECATED;

/**
 * Class BackendUtility
 */
class BackendUtility
{
    protected const DEPRECATED_SYS_DOMAIN = 'sys_domain will be removed in TYPO3 v10. Please consider upgrading to site configurations now.';

    protected static $rtc = [];

    /**
     * Get current page uid (normally from ?id=123)
     *
     * @param mixed $identifier
     * @param string $table
     *
     * @return int|string Returns the page ID or the folder ID when navigating in the file list
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function getPageIdentifier($identifier = null, $table = null)
    {
        // get id from given identifier
        if ('pages' === $table && is_numeric($identifier)) {
            return (int)$identifier;
        }

        // get id from ?id=123
        if (null !== ($getId = GeneralUtility::_GP('id'))) {
            return is_numeric($getId) ? (int)$getId : $getId;
        }

        // get id from AJAX request
        if (null !== ($pageId = GeneralUtility::_GP('pageId'))) {
            return (int)$pageId;
        }

        // get id from ?cmd[pages][123][delete]=1
        if (null !== ($cmd = GeneralUtility::_GP('cmd'))) {
            if (isset($cmd['pages']) && is_array($cmd['pages'])) {
                foreach (array_keys($cmd['pages']) as $pid) {
                    return (int)$pid;
                }
            }
        }

        // get id from ?popViewId=123
        if (null !== ($popViewId = GeneralUtility::_GP('popViewId'))) {
            return (int)$popViewId;
        }

        // get id from ?redirect=script.php?param1=a&id=123&param2=2
        if (null !== ($redirect = GeneralUtility::_GP('redirect'))) {
            $urlParts = parse_url($redirect);
            if (!empty($urlParts['query']) && stristr($urlParts['query'], 'id=')) {
                parse_str($urlParts['query'], $parameters);
                if (!empty($parameters['id'])) {
                    return (int)$parameters['id'];
                }
            }
        }

        $localConnection = DatabaseUtility::buildLocalDatabaseConnection();
        $tableNames = $localConnection->getSchemaManager()->listTableNames();

        // get id from record ?data[tt_content][13]=foo
        if (null !== ($data = GeneralUtility::_GP('data')) && is_array($data) && in_array(key($data), $tableNames)) {
            $table = key($data);
            $query = $localConnection->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $result = $query->select('pid')
                            ->from($table)
                            ->where($query->expr()->eq('uid', (int)key($data[$table])))
                            ->setMaxResults(1)
                            ->execute()
                            ->fetch(PDO::FETCH_ASSOC);
            if (false !== $result && isset($result['pid'])) {
                return (int)$result['pid'];
            }
        }

        // get id from rollback ?element=tt_content:42
        if (null !== ($rollbackFields = GeneralUtility::_GP('element')) && is_string($rollbackFields)) {
            $rollbackData = explode(':', $rollbackFields);
            if (count($rollbackData) > 1 && in_array($rollbackData[0], $tableNames)) {
                if ($rollbackData[0] === 'pages') {
                    return (int)$rollbackData[1];
                } else {
                    $query = $localConnection->createQueryBuilder();
                    $query->getRestrictions()->removeAll();
                    $result = $query->select('pid')
                                    ->from($rollbackData[0])
                                    ->where($query->expr()->eq('uid', (int)$rollbackData[1]))
                                    ->setMaxResults(1)
                                    ->execute()
                                    ->fetch(PDO::FETCH_ASSOC);
                    if (false !== $result && isset($result['pid'])) {
                        return (int)$result['pid'];
                    }
                }
            }
        }

        // Assume the record has been imported via DataHandler on the CLI
        // Also, this is the last fallback strategy
        if (!empty($table) && MathUtility::canBeInterpretedAsInteger($identifier)) {
            $query = $localConnection->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $row = $query->select('pid')
                         ->from($table)
                         ->where($query->expr()->eq('uid', (int)$identifier))
                         ->setMaxResults(1)
                         ->execute()
                         ->fetch(PDO::FETCH_ASSOC);
            if (isset($row['pid'])) {
                return (int)$row['pid'];
            }
        }

        return 0;
    }

    /**
     * Create an URI to edit a record
     *
     * @param string $tableName
     * @param int $identifier
     *
     * @return string
     * @throws RouteNotFoundException
     */
    public static function buildEditUri(string $tableName, int $identifier): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $uriParameters = [
            'edit' => [
                $tableName => [
                    $identifier => 'edit',
                ],
            ],
            'returnUrl' => $uriBuilder->buildUriFromRoute('web_In2publishCoreM1')->__toString(),
        ];
        $editUri = $uriBuilder->buildUriFromRoute('record_edit', $uriParameters);
        return $editUri->__toString();
    }

    /**
     * Create an URI to undo a record
     *
     * @param string $table
     * @param int $identifier
     *
     * @return string
     * @throws RouteNotFoundException
     */
    public static function buildUndoUri(string $table, int $identifier): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $route = GeneralUtility::_GP('route') ?: GeneralUtility::_GP('M');

        $returnParameters = [
            'id' => GeneralUtility::_GP('id'),
        ];
        foreach (GeneralUtility::_GET() as $name => $value) {
            if (is_array($value) && false !== strpos(strtolower($name), strtolower($route))) {
                $returnParameters[$name] = $value;
            }
        }

        $uriParameters = [
            'element' => $table . ':' . $identifier,
            'returnUrl' => $uriBuilder->buildUriFromRoutePath($route, $returnParameters)->__toString(),
        ];

        $undoRui = $uriBuilder->buildUriFromRoute('record_history', $uriParameters);
        return $undoRui->__toString();
    }

    /**
     * Please don't blame me for this.
     *
     * @param int $pageUid
     * @param string $stagingLevel
     *
     * @return mixed|string|null
     * @throws In2publishCoreException
     */
    public static function buildPreviewUri(int $pageUid, string $stagingLevel)
    {
        $pageRow = self::getPage($stagingLevel, $pageUid);
        if (empty($pageRow)) {
            return null;
        }
        $langField = $GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? null;
        $langParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? null;

        $language = 0;
        if (null !== $langField && array_key_exists($langField, $pageRow)) {
            $language = $pageRow[$langField];
            if ($language > 0 && null !== $langParentField) {
                $pageUid = $pageRow[$langParentField];
            }
        }
        $additionalQueryParams['_language'] = $language;

        $site = self::getSiteForPageIdentifier($pageUid, $stagingLevel);

        if (null === $site) {
            return self::processLegacySysDomainRecord($pageUid, $stagingLevel);
        }

        $buildPageUrl = self::getLocalUriClosure($site, $pageUid, $additionalQueryParams);
        if ('foreign' === $stagingLevel) {
            $buildPageUrl = self::getForeignUriClosure($buildPageUrl, $site, $language, $pageUid);
        }

        return $buildPageUrl();
    }

    /**
     * @return VariableFrontend
     *
     * @throws NoSuchCacheException
     */
    protected static function getRuntimeCache(): VariableFrontend
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
    }

    /**
     * @param int $pageIdentifier
     * @param string $stagingLevel
     *
     * @return Site|null
     * @throws In2publishCoreException
     */
    public static function getSiteForPageIdentifier(int $pageIdentifier, string $stagingLevel): ?Site
    {
        try {
            $pageIdentifier = self::determineDefaultLanguagePageIdentifier($pageIdentifier, $stagingLevel);
        } catch (PageDoesNotExistException $e) {
            return null;
        }

        if (isset(self::$rtc['site'][$stagingLevel][$pageIdentifier])) {
            return self::$rtc['site'][$stagingLevel][$pageIdentifier];
        }

        $site = null;
        if ($stagingLevel === DomainService::LEVEL_LOCAL) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            try {
                $site = $siteFinder->getSiteByPageId($pageIdentifier);
            } catch (SiteNotFoundException $e) {
            }
        } else {
            $foreignSiteFinder = GeneralUtility::makeInstance(ForeignSiteFinder::class);
            try {
                $site = $foreignSiteFinder->getSiteBaseByPageId($pageIdentifier);
            } catch (SiteNotFoundException $e) {
            }
        }
        if (!($site instanceof Site)) {
            $site = null;
        }
        return self::$rtc['site'][$stagingLevel][$pageIdentifier] = $site;
    }

    /**
     * @param int $pageIdentifier
     * @param string $stagingLevel
     *
     * @return int
     * @throws PageDoesNotExistException
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function determineDefaultLanguagePageIdentifier(
        int $pageIdentifier,
        string $stagingLevel
    ): int {
        $origPid = $pageIdentifier;

        if (isset(self::$rtc['languageParent'][$stagingLevel][$origPid])) {
            return self::$rtc['languageParent'][$stagingLevel][$origPid];
        }

        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $parentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];

        $query = DatabaseUtility::buildDatabaseConnectionForSide($stagingLevel)->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select($languageField, $parentField)
              ->from('pages')
              ->where($query->expr()->eq('uid', $query->createNamedParameter($pageIdentifier)))
              ->setMaxResults(1);
        $page = $query->execute()->fetch();

        if (empty($page)) {
            throw PageDoesNotExistException::forMissingPage($pageIdentifier, $stagingLevel);
        } elseif ($page[$languageField] > 0) {
            $pageIdentifier = (int)$page[$parentField];
        }

        return self::$rtc['languageParent'][$stagingLevel][$origPid] = $pageIdentifier;
    }

    /**
     * @param Closure $buildPageUrl
     * @param Site $site
     * @param int $language
     * @param int $pageUid
     *
     * @return Closure
     */
    protected static function getForeignUriClosure(
        Closure $buildPageUrl,
        Site $site,
        int $language,
        int $pageUid
    ): Closure {
        return static function () use ($buildPageUrl, $site, $language, $pageUid): string {
            // Please forgive me for this ugliest of all hacks. I tried everything.

            // temporarily point the pages table to the foreign database connection, to make PageRepository->getPage fetch the foreign page
            $backup = $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages'] ?? null;
            $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages'] = 'in2publish_foreign';

            $cacheIdentifier = self::getPageRepositoryPageCacheIdentifier($site, $language, $pageUid);

            // Remove the local page from the cache so the foreign page will be fetched instead of the cached local one.
            $runtimeCache = static::getRuntimeCache();
            $localCache = $runtimeCache->get($cacheIdentifier);
            $runtimeCache->remove($cacheIdentifier);

            // Build the url with the original callback.
            $url = $buildPageUrl();

            // Restore the cache if it existed or remove the foreign page from the cache.
            if (false !== $localCache) {
                $runtimeCache->set($cacheIdentifier, $localCache);
            } else {
                $runtimeCache->remove($cacheIdentifier);
            }

            // Reset the table mapping to make pages point to the local DB again
            if (null === $backup) {
                unset($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages']);
            } else {
                $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages'] = $backup;
            }

            return $url;
        };
    }

    /**
     * @param Site $site
     * @param int $pageUid
     * @param $additionalQueryParams
     *
     * @return Closure
     */
    protected static function getLocalUriClosure(Site $site, int $pageUid, $additionalQueryParams): Closure
    {
        return static function () use ($site, $pageUid, $additionalQueryParams) : ?string {
            try {
                return (string)$site->getRouter()->generateUri(
                    $pageUid,
                    $additionalQueryParams,
                    '',
                    RouterInterface::ABSOLUTE_URL
                );
            } catch (Throwable $throwable) {
                return null;
            }
        };
    }

    /**
     * @param Site $site
     * @param int $language
     * @param int $pageUid
     *
     * @return string
     * @throws AspectNotFoundException
     */
    protected static function getPageRepositoryPageCacheIdentifier(Site $site, int $language, int $pageUid): string
    {
        // Construct everything needed to build the cache identifier used for the PageRepository cache
        $siteLanguage = $site->getLanguageById((int)$language);
        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($siteLanguage));
        $sysLanguageUid = (int)$context->getPropertyFromAspect('language', 'id', 0);
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        return 'PageRepository_getPage_' . md5(
                implode(
                    '-',
                    [
                        $pageUid,
                        '',
                        $pageRepository->where_hid_del,
                        $sysLanguageUid,
                    ]
                )
            );
    }

    /**
     * @param string $stagingLevel
     * @param int $pageUid
     *
     * @return array|null
     */
    protected static function getPage(string $stagingLevel, int $pageUid): ?array
    {
        $connection = DatabaseUtility::buildDatabaseConnectionForSide($stagingLevel);
        $query = $connection->createQueryBuilder();
        $query->select('*')->from('pages')->where($query->expr()->eq('uid', $query->createNamedParameter($pageUid)));
        $statement = $query->execute();
        return $statement->fetch();
    }

    protected static function processLegacySysDomainRecord(int $pageUid, string $stagingLevel): ?string
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);

        if (version_compare(TYPO3_branch, '10.0', '>=')) {
            $logger->error('Can not identify site configuration for page.', ['page' => $pageUid]);
            // TYPO3 v10 does not have sys_domain records. If no site has been found we can't help it.
            return null;
        }
        self::triggerSysDomainDeprecationOnce();
        $logger->notice(
            'Can not identify site configuration for page.',
            ['page' => $pageUid, 'stagingLevel' => $stagingLevel]
        );

        $domainName = self::getDomainNameFromSysDomainForPage($pageUid, $stagingLevel);
        if (null === $domainName) {
            return null;
        }
        $uri = new Uri($domainName);
        $uri = UriUtility::normalizeUri($uri);
        $uri = $uri->withPath(rtrim($uri->getPath(), '/') . '/index.php')->withQuery($uri->getQuery() . '&id=' . $pageUid);
        return (string)$uri;
    }

    protected static function triggerSysDomainDeprecationOnce(): void
    {
        if (false === static::$rtc['sys_domain_deprecation_triggered'] ?? false) {
            trigger_error(self::DEPRECATED_SYS_DOMAIN, E_USER_DEPRECATED);
            static::$rtc['sys_domain_deprecation_triggered'] = true;
        }
    }

    protected static function getDomainNameFromSysDomainForPage(int $pageUid, string $stagingLevel): ?string
    {
        $domainService = GeneralUtility::makeInstance(DomainService::class);
        return $domainService->fetchInheritedSysDomainNameForPage($pageUid, $stagingLevel);
    }
}
