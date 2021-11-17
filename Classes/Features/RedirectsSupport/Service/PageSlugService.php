<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Service;

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

use In2code\In2publishCore\Service\Routing\SiteService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Site\Entity\Site;

class PageSlugService
{
    protected SiteService $siteService;

    protected ConnectionPool $connectionPool;

    public function __construct(SiteService $siteService, ConnectionPool $connectionPool)
    {
        $this->siteService = $siteService;
        $this->connectionPool = $connectionPool;
    }

    public function updateData(): void
    {
        $dataTableConnection = $this->connectionPool->getConnectionForTable('tx_in2publishcore_pages_slug_data');
        $query = $this->connectionPool->getQueryBuilderForTable('pages');
        $query->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $query->select('p.uid', 'p.slug', 'p.sys_language_uid')
              ->from('pages', 'p')
              ->leftJoin(
                  'p',
                  'tx_in2publishcore_pages_slug_data',
                  'd',
                  'p.uid = d.page_uid AND p.slug = d.page_slug AND p.sys_language_uid = d.page_language'
              )
              ->where($query->expr()->isNull('d.page_uid'));
        $statement = $query->execute();
        foreach ($statement as $row) {
            $uid = (int)$row['uid'];
            $languageId = (int)$row['sys_language_uid'];
            $slug = $row['slug'];

            $site = $this->siteService->getSiteForPidAndStagingLevel($uid, 'local');
            if (!$site instanceof Site) {
                continue;
            }
            $url = (string)$site->getRouter()->generateUri($uid, ['_language' => $languageId]);
            $dataTableConnection->insert(
                'tx_in2publishcore_pages_slug_data',
                [
                    'page_uid' => $uid,
                    'page_slug' => $slug,
                    'page_language' => $languageId,
                    'url' => $url,
                    'site_id' => $site->getIdentifier(),
                ]
            );
        }
    }
}
