<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Utility;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
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
use Doctrine\DBAL\Exception;
use In2code\In2publishCore\Service\Database\RawRecordService;
use In2code\In2publishCore\Service\Environment\ForeignEnvironmentService;
use In2code\In2publishCore\Service\Routing\SiteService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Throwable;
use TYPO3\CMS\Backend\Utility\BackendUtility as CoreBackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use function array_key_exists;
use function array_keys;
use function array_replace;
use function count;
use function current;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function md5;
use function parse_str;
use function parse_url;
use function rtrim;
use function stripos;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity) Maybe refactor this into a service with ordered strategies.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Can't help this without splitting
 */
class BackendUtility
{
    /**
     * Get current page uid (normally from ?id=123)
     *
     * @return int|string Returns the page ID or the folder ID when navigating in the file list
     *
     * See the class comment for more info
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     * @noinspection CallableParameterUseCaseInTypeContextInspection
     * @throws Exception
     */
    public static function getPageIdentifier(mixed $identifier = null, ?string $table = null): int|string
    {
        // get id from given identifier
        if ('pages' === $table && is_numeric($identifier)) {
            return (int)$identifier;
        }

        $localConnection = DatabaseUtility::buildLocalDatabaseConnection();
        if (null === $localConnection) {
            return 0;
        }
        $tableNames = $localConnection->createSchemaManager()->listTableNames();

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        if ($request instanceof ServerRequestInterface) {
            // get id from ?combined_identifier=1:/xyz/abcd
            $getCombinedIdentifier = $request->getParsedBody()['combined_identifier']
                ?? $request->getQueryParams()['combined_identifier']
                ?? null;

            if (null !== $getCombinedIdentifier) {
                return $getCombinedIdentifier;
            }

            // get id from AJAX request
            $pageId = $request->getParsedBody()['pageId']
                ?? $request->getQueryParams()['pageId']
                ?? null;

            if (null !== $pageId) {
                return (int)$pageId;
            }

            // get id from ?id=123
            $getId = $request->getParsedBody()['id']
                ?? $request->getQueryParams()['id']
                ?? null;

            if (null !== $getId) {
                return is_numeric($getId) ? (int)$getId : $getId;
            }

            // get id from ?cmd[pages][123][delete]=1
            $cmd = $request->getParsedBody()['cmd']
                ?? $request->getQueryParams()['cmd']
                ?? null;

            if (
                null !== $cmd
                && isset($cmd['pages'])
                && is_array($cmd['pages'])
            ) {
                /** @noinspection LoopWhichDoesNotLoopInspection */
                foreach (array_keys($cmd['pages']) as $pid) {
                    return (int)$pid;
                }
            }

            // get id from ?popViewId=123
            $popViewId = $request->getParsedBody()['popViewId']
                ?? $request->getQueryParams()['popViewId']
                ?? null;

            if (null !== $popViewId) {
                return (int)$popViewId;
            }

            // get id from ?redirect=script.php?param1=a&id=123&param2=2
            $redirect = $request->getParsedBody()['redirect']
                ?? $request->getQueryParams()['redirect']
                ?? null;

            if (null !== $redirect) {
                $urlParts = parse_url($redirect);
                if (!empty($urlParts['query']) && stripos($urlParts['query'], 'id=') !== false) {
                    parse_str($urlParts['query'], $parameters);
                    if (!empty($parameters['id'])) {
                        return (int)$parameters['id'];
                    }
                }
            }

            // get id from rollback ?element=tt_content:42
            $rollbackFields = $request->getParsedBody()['element']
                ?? $request->getQueryParams()['element']
                ?? null;

            if (null !== $rollbackFields && is_string($rollbackFields)) {
                $rollbackData = explode(':', $rollbackFields);
                if (count($rollbackData) > 1 && in_array($rollbackData[0], $tableNames)) {
                    if ($rollbackData[0] === 'pages') {
                        return (int)$rollbackData[1];
                    }

                    $query = $localConnection->createQueryBuilder();
                    $query->getRestrictions()->removeAll();
                    $result = $query->select('pid')
                                    ->from($rollbackData[0])
                                    ->where($query->expr()->eq('uid', (int)$rollbackData[1]))
                                    ->setMaxResults(1)
                                    ->executeQuery()
                                    ->fetchAssociative();
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
                         ->executeQuery()
                         ->fetchAssociative();
            if (isset($row['pid'])) {
                return (int)$row['pid'];
            }
        }

        return 0;
    }

    /**
     * Please don't blame me for this.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function buildPreviewUri(string $table, int $identifier, string $stagingLevel): ?UriInterface
    {
        $rawRecordService = GeneralUtility::makeInstance(RawRecordService::class);
        $row = $rawRecordService->getRawRecord($table, $identifier, $stagingLevel);

        if (empty($row)) {
            return null;
        }
        $excludeDokTypes = [
            PageRepository::DOKTYPE_SPACER,
            PageRepository::DOKTYPE_SYSFOLDER,
        ];

        if (
            'pages' === $table
            && array_key_exists('doktype', $row)
            && in_array($row['doktype'], $excludeDokTypes)
        ) {
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
                    $previewConfiguration['additionalGetParameters.'],
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
            return null;
        }

        $additionalQueryParams['_language'] = $language;
        $buildPageUrl = self::getLocalUriClosure($site, $pid, $additionalQueryParams);
        if ('foreign' === $stagingLevel) {
            $buildPageUrl = self::getForeignUriClosure($buildPageUrl, $site, $language, $pid);
        }

        return $buildPageUrl();
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
     */
    protected static function parseAdditionalGetParameters(array &$parameters, array $typoScript): void
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
     * @throws NoSuchCacheException
     */
    protected static function getRuntimeCache(): VariableFrontend
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }

    protected static function getForeignUriClosure(
        Closure $buildPageUrl,
        Site $site,
        int $language,
        int $pageUid
    ): Closure {
        return static function () use ($buildPageUrl, $site, $language, $pageUid): ?UriInterface {
            // Please forgive me for this ugliest of all hacks. I tried everything.

            // temporarily point the pages' table to the foreign database connection, to make PageRepository->getPage fetch the foreign page
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
                // Ignore exception
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

    protected static function getLocalUriClosure(Site $site, int $pageUid, array $additionalQueryParams): Closure
    {
        return static function () use ($site, $pageUid, $additionalQueryParams): ?UriInterface {
            try {
                return $site->getRouter()->generateUri(
                    (string)$pageUid,
                    $additionalQueryParams,
                    '',
                    RouterInterface::ABSOLUTE_URL,
                );
            } catch (Throwable $throwable) {
                return null;
            }
        };
    }

    /**
     * @throws AspectNotFoundException
     */
    protected static function getPageRepositoryPageCacheIdentifier(Site $site, int $language, int $pageUid): string
    {
        // Construct everything needed to build the cache identifier used for the PageRepository cache
        $siteLanguage = $site->getLanguageById($language);
        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($siteLanguage));
        $sysLanguageUid = (int)$context->getPropertyFromAspect('language', 'id', 0);
        $implode = implode('-', [$pageUid, '', 'pages.deleted=0', $sysLanguageUid]);
        return 'PageRepository_getPage_' . md5($implode);
    }
}
