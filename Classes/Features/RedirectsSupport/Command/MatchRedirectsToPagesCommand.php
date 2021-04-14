<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Command;

use In2code\In2publishCore\Features\RedirectsSupport\Service\PageSlugService;
use In2code\In2publishCore\Service\Context\ContextService;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SiteUrl
{
    /** @var Site */
    public $site;

    /** @var SiteLanguage */
    public $siteLanguage;

    public function __construct(Site $site, SiteLanguage $siteLanguage)
    {
        $this->site = $site;
        $this->siteLanguage = $siteLanguage;
    }

    public function getBase(): UriInterface
    {
        return $this->siteLanguage->getBase();
    }

    public function getUrl(): string
    {
        return (string)$this->siteLanguage->getBase();
    }

    public function getBaseUrl(): string
    {
        $uri = $this->getBase();
        return rtrim(substr((string)$uri, strlen($uri->getScheme()) + 3), '/');
    }

    public function getMatchLenght(string $host, string $slug)
    {
        if ('*' !== $host) {
            $baseUrl = $this->getBaseUrl();
            $fullUrl = $host . $slug;
            if (0 === strpos($fullUrl, $baseUrl)) {
                return strlen($baseUrl) + 1000;
            }
            return 0;
        }
        $path = $this->getBase()->getPath();
        if (0 === strpos($slug, $path)) {
            return strlen($path);
        }
        return 0;
    }
}

class MatchRedirectsToPagesCommand extends Command
{
    public const DESCRIPTION = <<<'TXT'
Scans the sys_redirects table for new redirects and tries to identify the page they are associated with.
TXT;
    public const IDENTIFIER = 'in2publish_core:redirectssupport:matchredirectstopages';

    protected function configure()
    {
        $this->setDescription(static::DESCRIPTION);
    }

    public function isEnabled()
    {
        return GeneralUtility::makeInstance(ContextService::class)->isLocal();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $sites = $siteFinder->getAllSites();

        /** @var SiteUrl[] $bases */
        $bases = [];

        foreach ($sites as $site) {
            foreach ($site->getLanguages() as $siteLanguage) {
                $bases[] = new SiteUrl($site, $siteLanguage);
            }
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class);

        $query = $connection->getQueryBuilderForTable('sys_redirect');
        $query->getRestrictions()->removeAll();
        $query->select('*')->from('sys_redirect');
        $statement = $query->execute();
        foreach ($statement as $row) {
            $host = $row['source_host'];
            $path = $row['source_path'];

            $sites = [];

            foreach ($bases as $basis) {
                $prio = $basis->getMatchLenght($host, $path);
                if ($prio > 0) {
                    $sites[$prio] = $basis;
                }
            }
            if (count($sites) <= 0) {
                continue;
            }
            foreach ($sites as $site) {
                $base = $site->getBase();
                $basePathLength = strlen($base->getPath());
                $pageSlug = '/' . ltrim(substr($path, $basePathLength), '/');

                $query = $connection->getQueryBuilderForTable('pages');
                $query->select('*')->from('pages')->where(
                    $query->expr()->eq('slug', $query->createNamedParameter($pageSlug))
                );
                $statement = $query->execute();
                foreach ($statement as $pageRow) {
                    $router = $site->site->getRouter();
                    $uri = $router->generateUri($pageRow['uid'], ['_language' => $pageRow['sys_language_uid']]);
                }
            }
        }

        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($bases, __CLASS__ . '@' . __LINE__, 20, false, true);

        return Command::SUCCESS;

        $pageSlugService = GeneralUtility::makeInstance(PageSlugService::class);
        $pageSlugService->updateData();

        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $query = $connection->getQueryBuilderForTable('sys_redirect');
        $query->getRestrictions()->removeAll();
        $query->select('r.*')
              ->from('sys_redirect', 'r')
              ->leftJoin('r', 'tx_in2publishcore_pages_redirects_mm', 'mm', 'r.uid = mm.redirect_uid')
              ->where($query->expr()->isNull('mm.redirect_id'))
              ->setMaxResults(20);
        $statement = $query->execute();
        foreach ($statement as $row) {
        }

        return Command::SUCCESS;
    }

}
