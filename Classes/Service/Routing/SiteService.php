<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Routing;

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

use In2code\In2publishCore\Domain\Service\ForeignSiteFinder;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Service\Database\RawRecordService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;

class SiteService implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const SITE_FINDER = [
        'local' => SiteFinder::class,
        'foreign' => ForeignSiteFinder::class,
    ];

    protected $cache = [];

    public function getSiteForPidAndStagingLevel(int $pid, string $side): ?Site
    {
        $pid = $this->determineDefaultLanguagePid($pid, $side);
        if (null === $pid) {
            return null;
        }
        return $this->fetchSiteBySide($pid, $side);
    }

    protected function determineDefaultLanguagePid(int $pageIdentifier, string $stagingLevel): ?int
    {
        $rawRecordService = GeneralUtility::makeInstance(RawRecordService::class);
        $row = $rawRecordService->getRawRecord('pages', $pageIdentifier, $stagingLevel);
        if (null === $row) {
            return null;
        }

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $l10nPointer = $tcaService->getTransOrigPointerField('pages');
        if (empty($l10nPointer)) {
            return $pageIdentifier;
        }
        $languageField = $tcaService->getLanguageField('pages');
        if (empty($languageField)) {
            return $pageIdentifier;
        }

        if ($row[$languageField] > 0 && $row[$l10nPointer] > 0) {
            $pageIdentifier = $row[$l10nPointer];
        }

        return $pageIdentifier;
    }

    protected function fetchSiteBySide(int $pid, string $side): ?Site
    {
        // TODO: check rootline to cache a site for all pages in the rootline
        if (!array_key_exists($pid, $this->cache['site'][$side] ?? [])) {
            $site = null;
            /** @var SiteFinder|ForeignSiteFinder $siteFinder */
            $siteFinder = GeneralUtility::makeInstance(self::SITE_FINDER[$side]);
            try {
                $site = $siteFinder->getSiteByPageId($pid);
            } catch (PageNotFoundException $e) {
                $site = null;
            } catch (SiteNotFoundException $e) {
                $this->logMissingSiteOnce($pid, $side);
            }

            $this->cache['site'][$side][$pid] = $site;
        }
        return $this->cache['site'][$side][$pid];
    }

    protected function logMissingSiteOnce(int $pid, string $side): void
    {
        if (isset($this->cache['trigger']['logMissingSiteOnce'])) {
            return;
        }
        $this->cache['trigger']['logMissingSiteOnce'] = true;
        $this->logger->error('Can not identify site configuration for page.', ['page' => $pid, 'side' => $side]);
    }
}
