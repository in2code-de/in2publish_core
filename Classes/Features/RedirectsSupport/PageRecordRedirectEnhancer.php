<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport;

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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Service\Routing\SiteService;
use In2code\In2publishCore\Utility\BackendUtility;
use PDO;
use Psr\Http\Message\UriInterface as Uri;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageRecordRedirectEnhancer
{
    /** @var SiteService */
    protected $siteService;

    /** @var Connection */
    protected $localDatabase;

    /** @var Connection */
    protected $foreignDatabase;

    protected $looseRedirects = [];

    public function __construct(SiteService $siteService, Connection $localDatabase, Connection $foreignDatabase)
    {
        $this->siteService = $siteService;
        $this->localDatabase = $localDatabase;
        $this->foreignDatabase = $foreignDatabase;
    }

    public function addRedirectsToPageRecord(RecordInterface $record): void
    {
        if ('pages' !== $record->getTableName() || ($pid = $record->getIdentifier()) < 1) {
            return;
        }

        // Find associated sys_redirects
        $commonRepo = CommonRepository::getDefaultInstance();
        $relatedRedirects = $commonRepo->findByProperties(
            [
                'tx_in2publishcore_page_uid' => $pid,
                'tx_in2publishcore_foreign_site_id' => null,
            ],
            false,
            'sys_redirect'
        );
        $record->addRelatedRecords($relatedRedirects);

        // The preview URL is not available if the record is deleted (because the SiteFinder uses
        // the RootlineUtility which does not support deleted pages)
        if ($record->isLocalRecordDeleted()) {
            return;
        }

        $uri = BackendUtility::buildPreviewUri('pages', $pid, 'local');
        // Find redirects by current url
        if (null !== $uri) {
            $rows = [];
            foreach ($relatedRedirects as $relatedRedirect) {
                $rows[$relatedRedirect->getIdentifier()] = [
                    'local' => $relatedRedirect->getLocalProperties(),
                    'foreign' => $relatedRedirect->getForeignProperties(),
                ];
            }

            $redirects = $this->collectRedirectsByUriRecursive($pid, $uri, $rows);
            foreach ($relatedRedirects as $relatedRedirect) {
                unset($redirects[$relatedRedirect->getIdentifier()]);
            }

            $relatedRedirects = [];
            foreach ($redirects as $rowSet) {
                $relatedRedirect = GeneralUtility::makeInstance(
                    Record::class,
                    'sys_redirect',
                    $rowSet['local'] ?? [],
                    $rowSet['foreign'] ?? [],
                    [],
                    []
                );
                $relatedRedirects[] = $relatedRedirect;
            }

            $record->addRelatedRecords($relatedRedirects);
        }

        $this->processLooseRedirects($pid);
    }

    public function collectRedirectsByUriRecursive(int $pid, Uri $uri, array $rows = [], array $seen = []): array
    {
        $uriStr = (string)$uri;
        if (isset($seen[$uriStr])) {
            return $rows;
        }
        $seen[$uriStr] = true;

        $query = $this->localDatabase->createQueryBuilder();
        $localRows = $this->selectRedirects($query, $uri, $rows);
        $foreignRows = [];

        $url = BackendUtility::buildPreviewUri('pages', $pid, 'foreign');
        if (null !== $url) {
            $query = $this->foreignDatabase->createQueryBuilder();
            $foreignRows = $this->selectRedirects($query, $url, $rows);
        }

        $found = [];

        foreach (array_unique(array_merge(array_keys($localRows), array_keys($foreignRows))) as $uid) {
            $localRow = $localRows[$uid] ?? null;
            $foreignRow = $foreignRows[$uid] ?? null;

            $this->collectLosseRedirects($localRow, 'local');
            $this->collectLosseRedirects($foreignRow, 'foreign');

            $rows[$uid] = [
                'local' => $localRow,
                'foreign' => $foreignRow,
            ];

            $found[$uid] = [
                'local' => $localRow,
                'foreign' => $foreignRow,
            ];
        }

        foreach ($found as $row) {
            $row = $row['local'] ?? $row['foreign'];
            $sourceHost = $row['source_host'];
            $sourcePath = $row['source_path'];
            // Don't search for redirects to root
            if ('/' === $sourcePath) {
                continue;
            }
            $nextUri = $uri->withHost($sourceHost)->withPath($sourcePath);
            $rows = $this->collectRedirectsByUriRecursive($pid, $nextUri, $rows, $seen);
        }

        return $rows;
    }

    protected function selectRedirects(QueryBuilder $query, Uri $uri, array $rows): array
    {
        if (empty($rows)) {
            $rows[] = 0;
        }
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from('sys_redirect')
              ->where(
                  $query->expr()->andX(
                      $query->expr()->orX(
                          $query->expr()->eq('source_host', $query->createNamedParameter($uri->getHost())),
                          $query->expr()->eq('source_host', "'*'")
                      ),
                      $query->expr()->eq('target', $query->createNamedParameter($uri->getPath())),
                      $query->expr()->notIn('uid', implode(array_keys($rows)))
                  )
              );
        $statement = $query->execute();
        $rows = $statement->fetchAllAssociative();
        return array_column($rows, null, 'uid');
    }

    protected function collectLosseRedirects(?array $localRow, string $side): void
    {
        if (
            null !== $localRow
            && null === $localRow['tx_in2publishcore_page_uid']
            && null === $localRow['tx_in2publishcore_foreign_site_id']
        ) {
            $this->looseRedirects[$side][] = $localRow['uid'];
        }
    }

    protected function processLooseRedirects($pid): void
    {
        if (!empty($this->looseRedirects['local'])) {
            $this->assignRedirects($this->localDatabase, $this->looseRedirects['local'], $pid);
        }
        if (!empty($this->looseRedirects['foreign'])) {
            $this->assignRedirects($this->foreignDatabase, $this->looseRedirects['foreign'], $pid);
        }
    }

    protected function assignRedirects(Connection $connection, array $uids, int $pid): void
    {
        $query = $connection->createQueryBuilder();
        $query->update('sys_redirect')
              ->set('tx_in2publishcore_page_uid', $pid, true, PDO::PARAM_INT)
              ->where($query->expr()->in('uid', $uids))
              ->execute();
    }
}
