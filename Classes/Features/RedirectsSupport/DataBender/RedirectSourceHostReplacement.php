<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\DataBender;

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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Service\Routing\SiteService;
use In2code\In2publishCore\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function in_array;

/**
 * Class RedirectSourceHostReplacement
 */
class RedirectSourceHostReplacement implements SingletonInterface
{
    const CHANGED_STATES = [
        RecordInterface::RECORD_STATE_CHANGED,
        RecordInterface::RECORD_STATE_ADDED,
        RecordInterface::RECORD_STATE_MOVED,
        RecordInterface::RECORD_STATE_MOVED_AND_CHANGED,
    ];

    protected $rtc = [];

    protected $siteService;

    public function __construct()
    {
        $this->siteService = GeneralUtility::makeInstance(SiteService::class);
    }

    public function replaceLocalWithForeignSourceHost(
        string $tableName,
        RecordInterface $record,
        CommonRepository $commonRepository
    ) {
        if ('sys_redirect' !== $tableName || !in_array($record->getState(), self::CHANGED_STATES)) {
            return [$tableName, $record, $commonRepository];
        }
        $properties = $record->getLocalProperties();
        if ('*' === $properties['source_host']) {
            return [$tableName, $record, $commonRepository];
        }

        $parentPageRecord = $record->getParentPageRecord();
        if (null === $parentPageRecord) {
            return [$tableName, $record, $commonRepository];
        }

        $url = BackendUtility::buildPreviewUri('pages', $parentPageRecord->getIdentifier(), 'foreign');
        $uri = new Uri($url);
        $newHost = $uri->getHost();

        $properties['source_host'] = $newHost;
        $record->setLocalProperties($properties);

        return [$tableName, $record, $commonRepository];
    }
}
