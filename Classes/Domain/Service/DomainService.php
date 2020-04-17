<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Service;

/*
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
 */

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Utility\BackendUtility as BackendUtilityAlias;
use In2code\In2publishCore\Utility\DatabaseUtility;
use PDO;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use function array_shift;
use function ltrim;
use function rtrim;
use function sprintf;
use function trigger_error;
use function trim;
use function version_compare;

use const E_USER_DEPRECATED;

/**
 * Class DomainService
 */
class DomainService implements SingletonInterface
{
    public const TABLE_NAME = 'sys_domain';
    public const LEVEL_LOCAL = 'local';
    public const LEVEL_FOREIGN = 'foreign';
    public const DEPRECATION_METHOD = 'The method %s is deprecated and will be removed in in2publish_core version 11.';

    /**
     * Get domain from root line without trailing slash
     *
     * @param RecordInterface $record
     * @param string $stagingLevel "local" or "foreign"
     * @param bool $addProtocol
     *
     * @return string
     */
    private $configContainer;

    /**
     * DomainService constructor.
     */
    public function __construct()
    {
        $this->configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
    }

    /**
     * @param RecordInterface $record
     * @param string $stagingLevel
     * @param bool $addProtocol
     *
     * @return mixed|string
     *
     * @throws In2publishCoreException
     *
     * @deprecated Use config filePreviewDomainName for sys_file or ::getDomainFromSiteConfigByPageId for anything else.
     */
    public function getFirstDomain(RecordInterface $record, $stagingLevel = self::LEVEL_LOCAL, $addProtocol = true)
    {
        trigger_error(sprintf(static::DEPRECATION_METHOD, __METHOD__), E_USER_DEPRECATED);
        $uri = $this->getDomainFromSiteConfigByPageId($record->getPageIdentifier(), $stagingLevel, $addProtocol);
        if (!empty($uri)) {
            return $uri;
        }
        switch ($record->getTableName()) {
            case 'pages':
                $domainName = $this->getFirstDomainInRootLineFromRelatedRecords($record, $stagingLevel);
                if ($domainName === null) {
                    $domainName = $this->getDomainFromPageIdentifier($record->getIdentifier(), $stagingLevel);
                }
                break;

            case 'sys_file':
                $domainName = $this->configContainer->get('filePreviewDomainName.' . $stagingLevel);
                break;

            default:
                $domainName = GeneralUtility::getIndpEnv('HTTP_HOST');
        }

        if ($addProtocol) {
            $domainName = '//' . $domainName;
        }

        return $domainName;
    }

    /**
     * TODO: Caching
     *
     * @param int $pageIdentifier
     * @param string $stagingLevel
     * @param bool $addProtocol
     *
     * @return string
     * @throws In2publishCoreException
     */
    public function getDomainFromSiteConfigByPageId(
        int $pageIdentifier,
        string $stagingLevel,
        bool $addProtocol
    ): string {
        if (0 === $pageIdentifier) {
            return '';
        }
        $site = BackendUtilityAlias::getSiteForPageIdentifier($pageIdentifier, $stagingLevel);
        if (null === $site) {
            return '';
        }
        $uri = (string)$site->getBase()->withScheme('');
        if ('/' === $uri && $stagingLevel === self::LEVEL_LOCAL) {
            if ($addProtocol) {
                $uri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
            } else {
                $uri = GeneralUtility::getIndpEnv('HTTP_HOST');
            }
        }
        if (!$addProtocol) {
            $uri = ltrim($uri, '/');
        }
        $uri = rtrim($uri, '/');
        return $uri;
    }

    /**
     * Find first domain record of related children records
     *
     * @param RecordInterface $record
     * @param string $stagingLevel
     *
     * @return string|null
     */
    protected function getFirstDomainInRootLineFromRelatedRecords(RecordInterface $record, $stagingLevel)
    {
        $relatedRecords = $record->getRelatedRecords();
        $domainRecordValues = [];
        if (!empty($relatedRecords[static::TABLE_NAME])) {
            foreach ($relatedRecords[static::TABLE_NAME] as $relatedDomainRecord) {
                /** @var RecordInterface $relatedDomainRecord */
                $domainProperties = ObjectAccess::getProperty($relatedDomainRecord, $stagingLevel . 'Properties');
                $domainRecordValues[$domainProperties['sorting']] = $domainProperties['domainName'];
            }
        }
        $domainName = array_shift($domainRecordValues);
        if ($domainName === null && $record->getParentRecord() !== null) {
            $domainName = static::getFirstDomainInRootLineFromRelatedRecords($record->getParentRecord(), $stagingLevel);
        }
        return $domainName;
    }

    /**
     * @param int $identifier UID of a pages record
     * @param string $stagingLevel
     *
     * @param bool $addProtocol
     *
     * @return string
     *
     * @throws In2publishCoreException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getDomainFromPageIdentifier($identifier, $stagingLevel, bool $addProtocol = false): string
    {
        if (0 === $identifier) {
            return '';
        }
        $uri = $this->getDomainFromSiteConfigByPageId($identifier, $stagingLevel, $addProtocol);
        if (empty($uri) && version_compare(TYPO3_branch, '10', '<')) {
            $uri = $this->fetchInheritedSysDomainNameForPage($identifier, $stagingLevel, $addProtocol);
            if (null !== $uri && $addProtocol) {
                $uri = (GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://') . $uri;
            }
        }
        return $uri;
    }

    /**
     * @param int $identifier
     * @param string $stagingLevel
     *
     * @return string
     */
    public function fetchInheritedSysDomainNameForPage(int $identifier, string $stagingLevel): ?string
    {
        $rootLine = BackendUtility::BEgetRootLine($identifier);
        foreach ($rootLine as $page) {
            $connection = DatabaseUtility::buildDatabaseConnectionForSide($stagingLevel);
            if (null !== $connection) {
                $query = $connection->createQueryBuilder();
                $query->getRestrictions()->removeAll();
                $domainRecord = $query->select('domainName')
                                      ->from(static::TABLE_NAME)
                                      ->where($query->expr()->eq('pid', (int)$page['uid']))
                                      ->andWhere($query->expr()->eq('hidden', 0))
                                      ->orderBy('sorting', 'ASC')
                                      ->setMaxResults(1)
                                      ->execute()
                                      ->fetch(PDO::FETCH_ASSOC);
                if (isset($domainRecord['domainName'])) {
                    return trim($domainRecord['domainName'], '/');
                }
            }
        }
        return null;
    }
}
