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
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Database\RawRecordService;
use In2code\In2publishCore\Service\Environment\ForeignEnvironmentService;
use In2code\In2publishCore\Service\Routing\SiteService;
use PDO;
use Throwable;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility as CoreBackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

use function array_key_exists;
use function array_keys;
use function array_replace;
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
    protected const TABLE_SYS_DOMAIN = 'sys_domain';

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
     * @param string $table
     * @param int $identifier
     * @param string $stagingLevel
     *
     * @return string|null
     * @throws In2publishCoreException
     */
    public static function buildPreviewUri(string $table, int $identifier, string $stagingLevel)
    {
        $rawRecordService = GeneralUtility::makeInstance(RawRecordService::class);
        $row = $rawRecordService->getRawRecord($table, $identifier, $stagingLevel);

        if (empty($row)) {
            return null;
        }
        $excludeDokTypes = [
            PageRepository::DOKTYPE_SPACER,
            PageRepository::DOKTYPE_RECYCLER,
            PageRepository::DOKTYPE_SYSFOLDER,
        ];
        if ('pages' === $table && array_key_exists('doktype', $row) && in_array($row['doktype'], $excludeDokTypes)) {
            return null;
        }

        $langField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
        $langParentField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;

        // Identify language
        $language = 0;
        if (null !== $langField && array_key_exists($langField, $row)) {
            $language = (int)$row[$langField];
        }

        // Identify language parent
        $languageParent = $identifier;
        if ($language > 0 && !empty($langParentField) && array_key_exists($langParentField, $row)) {
            $languageParent = $row[$langParentField] ?: $languageParent;
        }

        // Identify PID
        $pid = $row['pid'];
        if ('pages' === $table) {
            $pid = $row['uid'];
            if ($language > 0 && null !== $langParentField) {
                $pid = $languageParent;
            }
        }

        $additionalQueryParams = [];
        $previewConfiguration = CoreBackendUtility::getPagesTSconfig($pid)['TCEMAIN.']['preview.'][$table . '.'] ?? [];
        $hasPreviewConfiguration = !empty($previewConfiguration);

        if ('pages' !== $table && !$hasPreviewConfiguration) {
            // non-page records without preview configuration can't be viewed
            return null;
        }

        if ($hasPreviewConfiguration) {
            foreach ($previewConfiguration['fieldToParameterMap.'] ?? [] as $field => $parameterName) {
                $value = $row[$field];
                if ($field === 'uid') {
                    $value = $previewConfiguration['useDefaultLanguageRecord'] ? $languageParent : $identifier;
                }
                $additionalQueryParams[$parameterName] = $value;
            }
            if (isset($previewConfiguration['additionalGetParameters.'])) {
                $additionalGetParameters = [];
                self::parseAdditionalGetParameters(
                    $additionalGetParameters,
                    $previewConfiguration['additionalGetParameters.']
                );
                $additionalQueryParams = array_replace($additionalQueryParams, $additionalGetParameters);
            }
            if (isset($previewConfiguration['previewPageId'])) {
                $pid = (int)$previewConfiguration['previewPageId'];
            }
        }

        // Get site for identified PID
        $siteService = GeneralUtility::makeInstance(SiteService::class);
        $site = $siteService->getSiteForPidAndStagingLevel($pid, $stagingLevel);

        if (null === $site) {
            if (version_compare(TYPO3_branch, '10', '<')) {
                return self::processLegacySysDomainRecord($pid, $stagingLevel);
            }
            return null;
        }

        $additionalQueryParams['_language'] = $language;
        $buildPageUrl = self::getLocalUriClosure($site, $pid, $additionalQueryParams);
        if ('foreign' === $stagingLevel) {
            $buildPageUrl = self::getForeignUriClosure($buildPageUrl, $site, $language, $pid);
        }

        return $buildPageUrl();
    }

    protected static function processLegacySysDomainRecord(int $pageUid, string $stagingLevel): ?string
    {
        if (false === static::$rtc['sys_domain_deprecation_triggered'] ?? false) {
            trigger_error(self::DEPRECATED_SYS_DOMAIN, E_USER_DEPRECATED);
            static::$rtc['sys_domain_deprecation_triggered'] = true;
        }
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $logger->notice(
            'Can not identify site configuration for page.',
            ['page' => $pageUid, 'stagingLevel' => $stagingLevel]
        );

        $domainName = self::fetchInheritedSysDomainNameForPage($pageUid, $stagingLevel);

        if (null === $domainName) {
            return null;
        }
        $uri = new Uri($domainName);
        $uri = UriUtility::normalizeUri($uri);
        $uri = $uri->withPath(rtrim($uri->getPath(), '/') . '/index.php')
                   ->withQuery($uri->getQuery() . '&id=' . $pageUid);
        return (string)$uri;
    }

    /**
     * !!! COPY
     *
     * @param array $parameters Should be an empty array by default
     * @param array $typoScript The TypoScript configuration
     *
     * @see \TYPO3\CMS\Backend\Controller\EditDocumentController::parseAdditionalGetParameters
     *
     * Migrates a set of (possibly nested) GET parameters in TypoScript syntax to a plain array
     *
     * This basically removes the trailing dots of sub-array keys in TypoScript.
     * The result can be used to create a query string with GeneralUtility::implodeArrayForUrl().
     *
     */
    protected static function parseAdditionalGetParameters(array &$parameters, array $typoScript)
    {
        foreach ($typoScript as $key => $value) {
            if (is_array($value)) {
                $key = rtrim($key, '.');
                $parameters[$key] = [];
                self::parseAdditionalGetParameters($parameters[$key], $value);
            } else {
                $parameters[$key] = $value;
            }
        }
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
        return static function () use ($buildPageUrl, $site, $language, $pageUid): ?string {
            // Please forgive me for this ugliest of all hacks. I tried everything.

            // temporarily point the pages table to the foreign database connection, to make PageRepository->getPage fetch the foreign page
            $backup = $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages'] ?? null;
            $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages'] = 'in2publish_foreign';

            $cacheIdentifier = self::getPageRepositoryPageCacheIdentifier($site, $language, $pageUid);

            // Remove the local page from the cache so the foreign page will be fetched instead of the cached local one.
            $runtimeCache = static::getRuntimeCache();
            $localCache = $runtimeCache->get($cacheIdentifier);
            $runtimeCache->remove($cacheIdentifier);

            $foreignEnvironmentService = GeneralUtility::makeInstance(ForeignEnvironmentService::class);
            $foreignEncryptionKey = $foreignEnvironmentService->getEncryptionKey();
            if (empty($foreignEncryptionKey)) {
                return null;
            }

            $encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $foreignEncryptionKey;
            // Build the url with the original callback.
            try {
                return $buildPageUrl();
            } catch (Throwable $exception) {
            } finally {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $encryptionKey;

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
            }

            return null;
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

    public static function fetchInheritedSysDomainNameForPage(int $identifier, string $stagingLevel): ?string
    {
        $rootLine = CoreBackendUtility::BEgetRootLine($identifier);
        foreach ($rootLine as $page) {
            $connection = DatabaseUtility::buildDatabaseConnectionForSide($stagingLevel);
            $query = $connection->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('domainName')
                  ->from(self::TABLE_SYS_DOMAIN)
                  ->where($query->expr()->eq('pid', (int)$page['uid']))
                  ->andWhere($query->expr()->eq('hidden', 0))
                  ->orderBy('sorting', 'ASC')
                  ->setMaxResults(1);
            $statement = $query->execute();
            $domainRecord = $statement->fetch(PDO::FETCH_ASSOC);
            if (isset($domainRecord['domainName'])) {
                return trim($domainRecord['domainName'], '/');
            }
        }
        return null;
    }
}
