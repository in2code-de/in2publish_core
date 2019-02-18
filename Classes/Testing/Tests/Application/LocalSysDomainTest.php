<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Testing\Tests\Application;

/***************************************************************
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use In2code\In2publishCore\Testing\Tests\Database\LocalDatabaseTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use PDO;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_column;
use function array_merge;
use function version_compare;

/**
 * Class LocalSysDomainTest
 */
class LocalSysDomainTest implements TestCaseInterface
{
    const DOMAIN_TYPE_NONE = 'none';
    const DOMAIN_TYPE_LEGACY = 'legacy';
    const DOMAIN_TYPE_SITE = 'site';

    /**
     * @var Connection
     */
    protected $localConnection = null;

    /**
     * LocalSysDomainTest constructor.
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->localConnection = DatabaseUtility::buildLocalDatabaseConnection();
    }

    /**
     * @return TestResult
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function run(): TestResult
    {
        if (version_compare(TYPO3_branch, '9.3', '>=')) {
            $statement = $this->localConnection->select(
                ['uid'],
                'pages',
                ['is_siteroot' => '1', 'sys_language_uid' => '0']
            );
            if (!$statement->execute()) {
                return new TestResult('application.local_sites_query_error', TestResult::ERROR);
            }
            $pageIds = array_column($statement->fetchAll(PDO::FETCH_ASSOC), 'uid');
            if (empty($pageIds)) {
                return new TestResult('application.no_sites_found', TestResult::WARNING);
            }

            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);

            $results = [];
            foreach ($pageIds as $pageId) {
                $domainType = self::DOMAIN_TYPE_NONE;

                try {
                    $siteFinder->getSiteByRootPageId($pageId);
                    $domainType = self::DOMAIN_TYPE_SITE;
                } catch (SiteNotFoundException $exception) {
                    if (0 < $this->localConnection->count('*', 'sys_domain', ['hidden' => 0, 'pid' => $pageId])) {
                        $domainType = self::DOMAIN_TYPE_LEGACY;
                    }
                }

                $results[$domainType][] = $pageId;
            }

            $messages = $this->getMessagesForSitesWithoutDomain($results);
            $messages = array_merge($messages, $this->getMessagesForSitesWithSysDomain($results));
            $messages = array_merge($messages, $this->getMessagesForSitesWithConfig($results));

            if (!empty($results[self::DOMAIN_TYPE_NONE])) {
                return new TestResult('application.local_sites_config_missing', TestResult::ERROR, $messages);
            }

            if (!empty($results[self::DOMAIN_TYPE_LEGACY])) {
                return new TestResult('application.local_sites_config_legacy', TestResult::WARNING, $messages);
            }

            return new TestResult('application.local_sites_config', TestResult::OK, $messages);
        }

        // TYPO3 v8 or lower
        $statement = $this->localConnection->select(['uid'], 'pages', ['is_siteroot' => '1']);
        if (!$statement->execute()) {
            return new TestResult('application.local_sites_query_error', TestResult::ERROR);
        }
        $pageIds = array_column($statement->fetchAll(PDO::FETCH_ASSOC), 'uid');
        if (empty($pageIds)) {
            return new TestResult('application.no_sites_found', TestResult::WARNING);
        }

        foreach ($pageIds as $pageId) {
            if (0 === $this->localConnection->count('*', 'sys_domain', ['hidden' => 0, 'pid' => $pageId])) {
                return new TestResult('application.local_sys_domain_missing', TestResult::ERROR);
            }
        }
        return new TestResult('application.local_sys_domain_configured');
    }

    /**
     * @param $results
     * @return array
     */
    public function getMessagesForSitesWithConfig($results): array
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_SITE])) {
            foreach ($results[self::DOMAIN_TYPE_SITE] as $pageId) {
                $messages[] = 'OK: The root page ' . $pageId . ' has a site configuration.';
            }
        }
        return $messages;
    }

    /**
     * @param $results
     * @return array
     */
    public function getMessagesForSitesWithSysDomain($results): array
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_LEGACY])) {
            foreach ($results[self::DOMAIN_TYPE_LEGACY] as $pageId) {
                $messages[] = 'WARNING: The root page ' . $pageId . ' has no site configuration but a legacy domain.';
            }
        }
        return $messages;
    }

    /**
     * @param $results
     * @return array
     */
    public function getMessagesForSitesWithoutDomain($results): array
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_NONE])) {
            foreach ($results[self::DOMAIN_TYPE_NONE] as $pageId) {
                $messages[] = 'ERROR: The root page ' . $pageId . ' is missing a site configuration (and sys_domain).';
            }
        }
        return $messages;
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            LocalDatabaseTest::class,
        ];
    }
}
