<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Application;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use Doctrine\DBAL\Result;
use In2code\In2publishCore\Testing\Tests\TestResult;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

use function array_column;
use function array_key_exists;
use function array_merge;
use function parse_url;
use function sprintf;
use function strpos;

abstract class AbstractDomainTest
{
    public const DOMAIN_TYPE_NONE = 'none';
    public const DOMAIN_TYPE_SITE = 'site';
    public const DOMAIN_TYPE_SLASH_BASE = 'base';
    /**
     * @api Set this in the inheriting test class
     */
    protected string $prefix = '';

    abstract protected function getPageToSiteBaseMapping(): array;

    abstract protected function getConnection(): Connection;

    public function run(): TestResult
    {
        $statement = $this->findAllRootPages();
        $pageIds = array_column($statement->fetchAllAssociative(), 'uid');
        if (empty($pageIds)) {
            return new TestResult(sprintf('application.no_%s_sites_found', $this->prefix), TestResult::WARNING);
        }

        try {
            $results = $this->determineDomainTypes($pageIds);
        } catch (Throwable $exception) {
            return new TestResult(
                sprintf('application.%s_site_config_exception', $this->prefix),
                TestResult::ERROR,
                [$exception->getMessage()],
            );
        }

        $messages = $this->getMessagesForSitesWithoutDomain($results);
        $messages = array_merge($messages, $this->getMessagesForSitesWithSlashBase($results));
        $messages = array_merge($messages, $this->getMessagesForSitesWithConfig($results));

        if (!empty($results[self::DOMAIN_TYPE_NONE])) {
            return new TestResult(
                sprintf('application.%s_sites_config_missing', $this->prefix),
                TestResult::ERROR,
                $messages,
            );
        }

        if (!empty($results[self::DOMAIN_TYPE_SLASH_BASE])) {
            return new TestResult(
                sprintf('application.%s_site_config_slash', $this->prefix),
                TestResult::ERROR,
                $messages,
            );
        }

        return new TestResult(sprintf('application.%s_sites_config', $this->prefix), TestResult::OK, $messages);
    }

    public function getMessagesForSitesWithConfig(array $results): array
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_SITE])) {
            foreach ($results[self::DOMAIN_TYPE_SITE] as $pageId) {
                $messages[] = 'OK: The ' . $this->prefix . ' root page ' . $pageId . ' has a site configuration.';
            }
        }
        return $messages;
    }

    protected function getMessagesForSitesWithSlashBase(array $results): array
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_SLASH_BASE])) {
            foreach ($results[self::DOMAIN_TYPE_SLASH_BASE] as $pageId) {
                $messages[] = 'ERROR: The ' . $this->prefix . ' root page ' . $pageId
                    . ' has a site configuration without scheme and host. '
                    . 'These are required for the content publisher to generate preview and compare URLs.';
            }
        }
        return $messages;
    }

    public function getMessagesForSitesWithoutDomain(array $results): array
    {
        $messages = [];
        if (!empty($results[self::DOMAIN_TYPE_NONE])) {
            foreach ($results[self::DOMAIN_TYPE_NONE] as $pageId) {
                $messages[] = 'ERROR: The ' . $this->prefix . ' root page ' . $pageId
                    . ' is missing a site configuration.';
            }
        }
        return $messages;
    }

    /**
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    protected function findAllRootPages(): Result
    {
        $query = $this->getConnection()->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->getRestrictions()->add(new DeletedRestriction());
        $query->select('uid')
              ->from('pages')
              ->where(
                  $query->expr()->and(
                      $query->expr()->eq('is_siteroot', $query->createNamedParameter(1)),
                      $query->expr()->eq('sys_language_uid', $query->createNamedParameter(0)),
                  ),
              );
        return $query->executeQuery();
    }

    protected function determineDomainTypes(array $pageIds): array
    {
        $results = [];
        $pageIdToBaseMapping = $this->getPageToSiteBaseMapping();

        foreach ($pageIds as $pageId) {
            $domainType = $this->determineDomainType($pageIdToBaseMapping, $pageId);
            $results[$domainType][] = $pageId;
        }
        return $results;
    }

    protected function determineDomainType(array $pageIdToBaseMapping, int $pageId): string
    {
        if (isset($pageIdToBaseMapping[$pageId])) {
            $urlParts = parse_url($pageIdToBaseMapping[$pageId]);
            $hostIsSet = array_key_exists('host', $urlParts);
            if ($hostIsSet) {
                return self::DOMAIN_TYPE_SITE;
            }

            // parse_url a host without scheme will put the host in "path"
            $path = $urlParts['path'] ?? '/';
            if (0 !== strpos($path, '/')) {
                return self::DOMAIN_TYPE_SITE;
            }

            return self::DOMAIN_TYPE_SLASH_BASE;
        }
        return self::DOMAIN_TYPE_NONE;
    }
}
