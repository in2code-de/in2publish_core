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

use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Features\RedirectsSupport\RelationResolver\RedirectsRelationResolver;
use In2code\In2publishCore\Service\Routing\SiteService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageRecordRedirectEnhancer
 */
class PageRecordRedirectEnhancer
{
    protected $siteService;

    public function __construct()
    {
        $this->siteService = GeneralUtility::makeInstance(SiteService::class);
    }

    public function addRedirectsToPageRecord(RecordInterface $record, RecordFactory $recordFactory)
    {
        if ('pages' !== $record->getTableName() || ($pid = $record->getIdentifier()) < 1) {
            return [$record, $recordFactory];
        }
        $site = $this->siteService->getSiteForPidAndStagingLevel($pid, 'local');
        if (null === $site) {
            return [$record, $recordFactory];
        }
        $uri = $site->getRouter()->generateUri($pid);

        $redirectsRelationResolver = GeneralUtility::makeInstance(RedirectsRelationResolver::class);
        $redirects = $redirectsRelationResolver->collectRedirectsByUriRecursive($pid, $uri);
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

        return [$record, $recordFactory];
    }
}
