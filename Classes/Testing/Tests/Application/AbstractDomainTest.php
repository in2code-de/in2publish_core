<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Application;

use Doctrine\DBAL\Driver\Statement;
use Exception;
use In2code\In2publishCore\Testing\Tests\TestResult;
use PDO;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

use function array_column;
use function array_key_exists;
use function array_merge;
use function parse_url;
use function sprintf;
use function substr;

abstract class AbstractDomainTest
{
    public const DOMAIN_TYPE_NONE = 'none';
    public const DOMAIN_TYPE_LEGACY = 'legacy';
    public const DOMAIN_TYPE_SITE = 'site';
    public const DOMAIN_TYPE_SLASH_BASE = 'base';

    /**
     * @var string
     * @api Set this in the inheriting test class
     */
    protected $prefix = '';

    abstract protected function getPageToSiteBaseMapping(): array;

    abstract protected function getConnection(): Connection;

    public function run(): TestResult
    {
        $statement = $this->findAllRootPages();
        if (0 !== $statement->errorCode()) {
            return new TestResult(sprintf('application.no_%s_sites_found', $this->prefix), TestResult::WARNING);
        }
        $pageIds = array_column($statement->fetchAll(PDO::FETCH_ASSOC), 'uid');
        if (empty($pageIds)) {
            return new TestResult(sprintf('application.no_%s_sites_found', $this->prefix), TestResult::WARNING);
        }

        $results = $this->determineDomainTypes($pageIds);

        $messages = $this->getMessagesForSitesWithoutDomain($results);
        $messages = array_merge($messages, $this->getMessagesForSitesWithSlashBase($results));
        $messages = array_merge($messages, $this->getMessagesForSitesWithSysDomain($results));
        $messages = array_merge($messages, $this->getMessagesForSitesWithConfig($results));

        if (!empty($results[self::DOMAIN_TYPE_NONE])) {
            return new TestResult(
                sprintf('application.%s_sites_config_missing', $this->prefix),
                TestResult::ERROR,
                $messages
            );
        }

        if (!empty($results[self::DOMAIN_TYPE_SLASH_BASE])) {
            return new TestResult(
                sprintf('application.%s_site_config_slash', $this->prefix),
                TestResult::ERROR,
                $messages
            );
        }

        if (!empty($results[self::DOMAIN_TYPE_LEGACY])) {
            return new TestResult(
                sprintf('application.%s_sites_config_legacy', $this->prefix),
                TestResult::WARNING,
                $messages
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
                $messages[] = 'WARNING: The ' . $this->prefix . ' root page ' . $pageId
                              . ' has no site configuration but a legacy domain.';
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
                $messages[] = 'ERROR: The ' . $this->prefix . ' root page ' . $pageId
                              . ' has a site configuration without scheme and host. '
                              . 'These are required for the content publisher to generate preview and compare URLs.';
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
                $messages[] = 'ERROR: The ' . $this->prefix . ' root page ' . $pageId
                              . ' is missing a site configuration (and sys_domain).';
            }
        }
        return $messages;
    }

    protected function findAllRootPages(): Statement
    {
        $query = $this->getConnection()->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->getRestrictions()->add(new DeletedRestriction());
        $query->select('uid')
              ->from('pages')
              ->where(
                  $query->expr()->andX(
                      $query->expr()->eq('is_siteroot', $query->createNamedParameter(1)),
                      $query->expr()->eq('sys_language_uid', $query->createNamedParameter(0))
                  )
              );
        return $query->execute();
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
            if ('/' !== substr($path, 0, 1)) {
                return self::DOMAIN_TYPE_SITE;
            }

            return self::DOMAIN_TYPE_SLASH_BASE;
        }
        if ($this->countAllSysDomainRecordsForPage($pageId) > 0) {
            return self::DOMAIN_TYPE_LEGACY;
        }
        return self::DOMAIN_TYPE_NONE;
    }

    protected function countAllSysDomainRecordsForPage(int $pageUid): int
    {
        $query = $this->getConnection()->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->getRestrictions()->add(new DeletedRestriction());
        $query->select('COUNT(uid)')
              ->from('sys_domain')
              ->where(
                  $query->expr()->eq('pid', $query->createNamedParameter($pageUid))
              );
        $statement = $query->execute();
        if (0 !== $statement->errorCode()) {
            throw new Exception();
        }
        return (int)$statement->fetchColumn(0);
    }
}
