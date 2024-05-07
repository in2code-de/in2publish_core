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

use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Event\RecordWasSelectedForPublishing;
use In2code\In2publishCore\Service\ForeignSiteFinderInjection;
use In2code\In2publishCore\Utility\BackendUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function in_array;

class RedirectSourceHostReplacement implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ForeignSiteFinderInjection;

    protected const CHANGED_STATES = [
        Record::S_CHANGED,
        Record::S_ADDED,
        Record::S_MOVED,
    ];

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function replaceLocalWithForeignSourceHost(RecordWasSelectedForPublishing $event): void
    {
        $record = $event->getRecord();
        if (
            'sys_redirect' !== $record->getClassification()
            || !in_array($record->getState(), self::CHANGED_STATES, true)
        ) {
            return;
        }

        // 1. Check wildcard
        $properties = $record->getLocalProps();
        if ('*' === $properties['source_host']) {
            return;
        }

        // 2. Check site
        $siteId = $properties['tx_in2publishcore_foreign_site_id'];
        if ('*' === $siteId) {
            $properties['source_host'] = '*';
            $record->setLocalProps($properties);
        }
        if (null !== $siteId) {
            $site = $this->foreignSiteFinder->getSiteByIdentifier($siteId);
            if (null === $site) {
                $this->logger->alert(
                    'A redirect has an associated site, but that site does not exist',
                    ['uid' => $record->getId()],
                );
                return;
            }

            $properties['source_host'] = $site->getBase()->getHost();
            $record->setLocalProps($properties);
            return;
        }

        // 3. Check page
        $associatedPage = $properties['tx_in2publishcore_page_uid'];
        if (null !== $associatedPage) {
            $url = BackendUtility::buildPreviewUri('pages', $associatedPage, 'foreign');
            if (null === $url) {
                return;
            }
            $newHost = $url->getHost();

            $properties['source_host'] = $newHost;
            $record->setLocalProps($properties);
            return;
        }

        // 4. Check Target
        $target = $properties['target'];
        if (null !== $target && str_contains($target, 't3://')) {
            $coreLinkService = GeneralUtility::makeInstance(LinkService::class);
            $linkAttributes = $coreLinkService->resolveByStringRepresentation($target);
            if (!empty($linkAttributes['pageuid'])) {
                $url = BackendUtility::buildPreviewUri('pages', $linkAttributes['pageuid'], 'foreign');
                if (null === $url) {
                    return;
                }
                $newHost = $url->getHost();

                $properties['source_host'] = $newHost;
                $record->setLocalProps($properties);
                return;
            }
        }

        $this->logger->alert(
            'A redirect without an associated page or site is going to be published',
            ['uid' => $record->getId()],
        );
    }
}
