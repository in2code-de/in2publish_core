<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Repository;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class SysRedirectRepository extends Repository
{
    public function findRawByUris(Connection $connection, array $uris, array $exceptUid): array
    {
        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();

        $predicates = [];

        foreach ($uris as $uri) {
            if ($uri->getHost() === '*') {
                // If the "parent" redirect does not have a host it does not matter which host the redirect has, which
                // redirects to the host-less redirect. It just belongs.
                $predicates[] = $query->expr()->eq('target', $query->createNamedParameter($uri->getPath()));
            } else {
                $predicates[] = $query->expr()->andX(
                    $query->expr()->eq('target', $query->createNamedParameter($uri->getPath())),
                    $query->expr()->orX(
                        $query->expr()->eq('source_host', $query->createNamedParameter($uri->getHost())),
                        $query->expr()->eq('source_host', $query->createNamedParameter('*'))
                    )
                );
            }
        }

        if (!empty($exceptUid)) {
            foreach ($exceptUid as &$uid) {
                $uid = (int)$uid;
            }
            unset($uid);
            $predicates = [
                $query->expr()->andX(
                    $query->expr()->notIn('uid', $exceptUid),
                    $query->expr()->orX(...$predicates)
                ),
            ];
        }

        $query->select('*')->from('sys_redirect')->where(...$predicates);
        $result = $query->execute();
        return $result->fetchAllAssociative();
    }

    public function findRawByUids(Connection $connection, array $uids): array
    {
        foreach ($uids as &$uid) {
            $uid = (int)$uid;
        }
        unset($uid);
        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from('sys_redirect')
              ->where($query->expr()->in('uid', $uids));
        $result = $query->execute();
        return $result->fetchAllAssociative();
    }

    public function findForPublishing(array $uidList, array $filter = [])
    {
        $query = $this->getQueryForRedirectsToBePublished($uidList);
        $and[] = $query->greaterThan('uid', 0);
        if (isset($filter['source_host']) && $filter['source_host'] != '') {
            $and[] = $query->equals('source_host', $filter['source_host']);
        }
        if (isset($filter['source_path']) && $filter['source_path'] != '') {
            $and[] = $query->like('source_path', '%' . $filter['source_path'] . '%');
        }
        if (isset($filter['target']) && $filter['target'] != '') {
            $and[] = $query->like('target', '%' . $filter['target'] . '%');
        }
        if (isset($filter['status_code']) && $filter['status_code'] != '') {
            $and[] = $query->equals('target_statuscode', $filter['status_code']);
        }
        $query->matching($query->logicalAnd($and));
        return $query->execute();
    }

    public function findHostsOfRedirects(): array
    {
        return $this->getQueryBuilder()
                    ->select('source_host as name')
                    ->from('sys_redirect')
                    ->orderBy('source_host')
                    ->groupBy('source_host')
                    ->execute()
                    ->fetchAllAssociative();
    }

    public function findStatusCodesOfRedirects(): array
    {
        return $this->getQueryBuilder()
                    ->select('target_statuscode as code')
                    ->from('sys_redirect')
                    ->orderBy('target_statuscode')
                    ->groupBy('target_statuscode')
                    ->execute()
                    ->fetchAllAssociative();
    }

    protected function getQueryForRedirectsToBePublished(array $uidList): QueryInterface
    {
        $query = $this->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setRespectSysLanguage(false);
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIncludeDeleted(true);
        if (!empty($uidList)) {
            $query->matching(
                $query->logicalOr(
                    [
                        $query->equals('deleted', 0),
                        $query->logicalNot($query->in('uid', $uidList)),
                    ]
                )
            );
        }
        return $query;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()
                     ->removeAll();
        return $queryBuilder;
    }
}
