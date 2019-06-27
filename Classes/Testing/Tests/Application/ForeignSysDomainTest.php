<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Testing\Tests\Application;

/*
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
 */

use In2code\In2publishCore\Command\StatusCommandController;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest;
use In2code\In2publishCore\Testing\Tests\Database\ForeignDatabaseTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use PDO;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_column;
use function array_merge;
use function base64_decode;
use function json_decode;

/**
 * Class ForeignSysDomainTest
 */
class ForeignSysDomainTest implements TestCaseInterface
{
    const DOMAIN_TYPE_NONE = 'none';
    const DOMAIN_TYPE_LEGACY = 'legacy';
    const DOMAIN_TYPE_SITE = 'site';
    const DOMAIN_TYPE_SLASH_BASE = 'base';

    /**
     * @var RemoteCommandDispatcher
     */
    protected $rceDispatcher;

    /**
     * @var Connection
     */
    protected $foreignConnection = null;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->rceDispatcher = GeneralUtility::makeInstance(RemoteCommandDispatcher::class);
        try {
            $this->foreignConnection = DatabaseUtility::buildForeignDatabaseConnection();
        } catch (Throwable $throwable) {
            // Dependency ForeignDatabaseTest will fail if this fails
        }
    }

    /**
     * @return TestResult
     */
    public function run(): TestResult
    {
        $statement = $this->foreignConnection->select(
            ['uid'],
            'pages',
            ['is_siteroot' => '1', 'sys_language_uid' => '0']
        );
        if (!$statement->execute()) {
            return new TestResult('application.foreign_sites_query_error');
        }
        $pageIds = array_column($statement->fetchAll(PDO::FETCH_ASSOC), 'uid');
        if (empty($pageIds)) {
            return new TestResult('application.no_foreign_sites_found', TestResult::WARNING);
        }
        try {
            $shortSiteConfig = $this->getForeignSiteConfig();
        } catch (ForeignSiteConfigUnavailableException $exception) {
            return new TestResult(
                'application.foreign_site_config_error',
                TestResult::ERROR,
                [
                    'error' => $exception->getErrorString(),
                    'ouput' => $exception->getOutputString(),
                    'status' => (string)$exception->getExitStatus(),
                ]
            );
        }

        $pageIdToBaseMapping = array_combine(
            array_column($shortSiteConfig, 'rootPageId'),
            array_column($shortSiteConfig, 'base')
        );

        $results = [];
        foreach ($pageIds as $pageId) {
            $domainType = 'none';
            if (isset($pageIdToBaseMapping[$pageId])) {
                if ($pageIdToBaseMapping[$pageId] === '/') {
                    $domainType = self::DOMAIN_TYPE_SLASH_BASE;
                } else {
                    $domainType = self::DOMAIN_TYPE_SITE;
                }
            } elseif (0 < $this->foreignConnection->count('*', 'sys_domain', ['hidden' => 0, 'pid' => $pageId])) {
                $domainType = self::DOMAIN_TYPE_LEGACY;
            }
            $results[$domainType][] = $pageId;
        }

        $messages = $this->getMessagesForSitesWithoutDomain($results);
        $messages = array_merge($messages, $this->getMessagesForSitesWithSlashBase($results));
        $messages = array_merge($messages, $this->getMessagesForSitesWithSysDomain($results));
        $messages = array_merge($messages, $this->getMessagesForSitesWithConfig($results));

        if (!empty($results[self::DOMAIN_TYPE_NONE])) {
            return new TestResult('application.foreign_sites_config_missing', TestResult::ERROR, $messages);
        }

        if (!empty($results[self::DOMAIN_TYPE_SLASH_BASE])) {
            return new TestResult('application.foreign_site_config_slash', TestResult::ERROR, $messages);
        }

        if (!empty($results[self::DOMAIN_TYPE_LEGACY])) {
            return new TestResult('application.foreign_sites_config_legacy', TestResult::WARNING, $messages);
        }

        return new TestResult('application.foreign_sites_config', TestResult::OK, $messages);
    }

    /**
     * @return array
     * @throws ForeignSiteConfigUnavailableException
     */
    public function getForeignSiteConfig(): array
    {
        $request = GeneralUtility::makeInstance(RemoteCommandRequest::class);
        $request->setCommand(StatusCommandController::SHORT_SITE_CONFIGURATION);

        $response = $this->rceDispatcher->dispatch($request);

        if ($response->isSuccessful()) {
            $responseParts = GeneralUtility::trimExplode(':', $response->getOutputString());
            $base64encoded = $responseParts[1];
            $jsonEncoded = base64_decode($base64encoded);
            $shortSiteConfig = json_decode($jsonEncoded, true);
        } else {
            throw ForeignSiteConfigUnavailableException::fromFailedRceResponse($response);
        }
        return $shortSiteConfig;
    }

    /**
     * @param $results
     *
     * @return array
     */
    public function getMessagesForSitesWithConfig($results): array
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_SITE])) {
            foreach ($results[self::DOMAIN_TYPE_SITE] as $pageId) {
                $messages[] = 'OK: The foreign root page ' . $pageId . ' has a site configuration.';
            }
        }
        return $messages;
    }

    /**
     * @param array $results
     *
     * @return array
     */
    protected function getMessagesForSitesWithSlashBase(array $results)
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_SLASH_BASE])) {
            foreach ($results[self::DOMAIN_TYPE_SLASH_BASE] as $pageId) {
                $messages[] = 'ERROR: The foreign root page ' . $pageId
                              . ' has a site configuration with "/" as base.';
            }
        }
        return $messages;
    }

    /**
     * @param $results
     *
     * @return array
     */
    public function getMessagesForSitesWithSysDomain($results): array
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_LEGACY])) {
            foreach ($results[self::DOMAIN_TYPE_LEGACY] as $pageId) {
                $messages[] = 'WARNING: The foreign root page ' . $pageId
                              . ' has no site configuration but a legacy domain.';
            }
        }
        return $messages;
    }

    /**
     * @param $results
     *
     * @return array
     */
    public function getMessagesForSitesWithoutDomain($results): array
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_NONE])) {
            foreach ($results[self::DOMAIN_TYPE_NONE] as $pageId) {
                $messages[] = 'ERROR: The foreign root page ' . $pageId
                              . ' is missing a site configuration (and sys_domain).';
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
            ForeignDatabaseTest::class,
            RemoteAdapterTest::class,
        ];
    }
}
